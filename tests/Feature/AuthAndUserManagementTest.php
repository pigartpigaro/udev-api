<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthAndUserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->seed(AccessControlSeeder::class);
    }

    public function test_user_can_log_in_with_username_instead_of_email(): void
    {
        $user = User::factory()->create([
            'email' => 'login-check@example.test',
            'username' => 'login.check',
            'password' => 'a-long-test-password',
        ]);

        $csrfToken = 'udev-auth-login-test-token';

        $this->withSession(['_token' => $csrfToken])
            ->withHeader('Origin', 'http://localhost:5173')
            ->withHeader('X-CSRF-TOKEN', $csrfToken)
            ->postJson('/api/v1/auth/login', [
            'username' => 'LOGIN.CHECK',
            'password' => 'a-long-test-password',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.username', 'login.check');
    }

    public function test_workspace_admin_can_create_a_member_only_in_the_active_workspace(): void
    {
        $admin = User::factory()->create(['role' => 'member']);
        $workspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();
        DB::table('workspace_user')->where('workspace_id', $workspace->id)->where('user_id', $admin->id)->update(['role' => 'admin']);

        $response = $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $workspace->id)
            ->postJson('/api/v1/access-control/users', [
                'name' => 'Anggota Baru',
                'username' => 'anggota.baru',
                'email' => 'anggota.baru@example.test',
                'password' => '123456',
                'password_confirmation' => '123456',
            ]);

        $response->assertCreated()
            ->assertJsonPath('user.username', 'anggota.baru')
            ->assertJsonPath('user.role', 'member');

        $created = User::query()->where('username', 'anggota.baru')->firstOrFail();
        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $created->id,
            'role' => 'member',
            'is_owner' => false,
        ]);
        $this->assertSame(1, DB::table('workspace_user')->where('user_id', $created->id)->count());
    }

    public function test_member_cannot_create_users_even_if_endpoint_is_called_directly(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $workspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();

        $this->actingAs($member)->withHeader('X-Workspace-ID', (string) $workspace->id)
            ->postJson('/api/v1/access-control/users', [
                'name' => 'Tidak Boleh',
                'username' => 'tidak.boleh',
                'email' => 'tidak.boleh@example.test',
                'password' => 'password-awal-123',
                'password_confirmation' => 'password-awal-123',
            ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['username' => 'tidak.boleh']);
    }

    public function test_new_member_password_must_have_at_least_six_characters(): void
    {
        $admin = User::factory()->create(['role' => 'member']);
        $workspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();
        DB::table('workspace_user')->where('workspace_id', $workspace->id)->where('user_id', $admin->id)->update(['role' => 'admin']);

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $workspace->id)
            ->postJson('/api/v1/access-control/users', [
                'name' => 'Anggota Singkat',
                'username' => 'anggota.singkat',
                'email' => 'anggota.singkat@example.test',
                'password' => '12345',
                'password_confirmation' => '12345',
            ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_workspace_admin_can_list_users_and_update_only_the_active_workspace_role(): void
    {
        $admin = User::factory()->create(['role' => 'member']);
        $workspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();
        DB::table('workspace_user')->where('workspace_id', $workspace->id)->where('user_id', $admin->id)->update(['role' => 'admin']);

        $target = User::factory()->create(['username' => 'role.target', 'role' => 'member']);
        $otherWorkspace = Workspace::query()->create(['name' => 'Workspace Terpisah', 'slug' => 'workspace-terpisah']);
        DB::table('workspace_user')->where('workspace_id', $workspace->id)->where('user_id', $target->id)->update(['role' => 'member']);
        DB::table('workspace_user')->insert([
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $target->id,
            'role' => 'member',
            'is_owner' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $workspace->id)
            ->getJson('/api/v1/access-control/users')
            ->assertOk()
            ->assertJsonFragment(['username' => 'role.target', 'role' => 'member', 'is_owner' => false]);

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $workspace->id)
            ->patchJson('/api/v1/access-control/users/'.$target->id.'/role', ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('workspace_user', ['workspace_id' => $workspace->id, 'user_id' => $target->id, 'role' => 'admin']);
        $this->assertDatabaseHas('workspace_user', ['workspace_id' => $otherWorkspace->id, 'user_id' => $target->id, 'role' => 'member']);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role' => 'member']);
    }

    public function test_workspace_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'member']);
        $workspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();
        DB::table('workspace_user')->where('workspace_id', $workspace->id)->where('user_id', $admin->id)->update(['role' => 'admin']);

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $workspace->id)
            ->patchJson('/api/v1/access-control/users/'.$admin->id.'/role', ['role' => 'member'])
            ->assertUnprocessable()->assertJsonValidationErrors('role');

    }
}
