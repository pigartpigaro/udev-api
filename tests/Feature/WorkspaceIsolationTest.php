<?php

namespace Tests\Feature;

use App\Models\AccessControl\Permission;
use App\Models\Master\Customer;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkspaceIsolationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_workspace_header_scopes_lists_and_route_model_binding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mainWorkspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();
        $otherWorkspace = Workspace::create(['name' => 'Workspace Tes Terpisah', 'slug' => 'workspace-tes-terpisah']);
        $mainCustomer = Customer::create(['name' => 'Pelanggan Khusus Workspace Utama']);
        $otherCustomer = Customer::forceCreate(['workspace_id' => $otherWorkspace->id, 'name' => 'Pelanggan Khusus Workspace Lain']);

        DB::table('workspace_user')->insert([
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'is_owner' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = Permission::query()->where('key', 'master.customers.manage')->value('id');
        DB::table('role_permissions')->insert([
            'workspace_id' => $otherWorkspace->id,
            'role' => 'admin',
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $mainWorkspace->id)
            ->getJson('/api/v1/customers?search=Khusus%20Workspace%20Lain')
            ->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $otherWorkspace->id)
            ->getJson('/api/v1/customers?search=Khusus%20Workspace%20Lain')
            ->assertOk()->assertJsonPath('data.0.id', $otherCustomer->id);

        $this->actingAs($admin)->withHeader('X-Workspace-ID', (string) $otherWorkspace->id)
            ->getJson('/api/v1/customers/'.$mainCustomer->id)->assertNotFound();

        $this->assertNotSame($mainWorkspace->id, $otherWorkspace->id);
    }

    public function test_workspace_admin_can_update_workspace_name_and_manage_member_permissions_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $workspace = Workspace::query()->where('slug', 'workspace-utama')->firstOrFail();
        $originalWorkspaceName = $workspace->name;
        $headers = ['X-Workspace-ID' => (string) $workspace->id];

        $this->actingAs($admin)->withHeaders($headers)->getJson('/api/v1/navigation')
            ->assertOk()->assertJsonFragment(['route_name' => 'settings.workspace.index'])
            ->assertJsonFragment(['route_name' => 'settings.roles.index']);

        $this->actingAs($admin)->withHeaders($headers)->getJson('/api/v1/workspaces/current')
            ->assertOk()->assertJsonPath('workspace.name', $originalWorkspaceName);

        $updatedWorkspace = $this->actingAs($admin)->withHeaders($headers)
            ->patchJson('/api/v1/workspaces/current', ['name' => 'Nama Workspace Tes Sementara']);
        DB::table('workspaces')->where('id', $workspace->id)->update(['name' => $originalWorkspaceName]);
        $updatedWorkspace->assertOk()->assertJsonPath('workspace.name', 'Nama Workspace Tes Sementara');

        $this->actingAs($admin)->withHeaders($headers)->getJson('/api/v1/access-control/roles/member/permission-options')
            ->assertOk()->assertJsonMissing(['key' => 'workspaces.manage'])
            ->assertJsonMissing(['key' => 'access-control.roles.manage']);

        $protectedId = Permission::query()->where('key', 'workspaces.manage')->value('id');
        $this->actingAs($admin)->withHeaders($headers)->putJson('/api/v1/access-control/roles/member/permissions', [
            'permission_ids' => [$protectedId],
        ])->assertUnprocessable();
    }
}
