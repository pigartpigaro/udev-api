<?php

namespace Tests\Feature;

use App\Models\Master\ProjectType;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProjectTypeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_can_create_project_type_with_generated_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/v1/master/project-types', [
            'name' => 'Aplikasi Web Uji',
            'description' => 'Tipe untuk pengujian',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('project_type.name', 'Aplikasi Web Uji')
            ->assertJsonPath('project_type.is_active', true);

        $this->assertMatchesRegularExpression('/^U-TP\\d{4,}$/', $response->json('project_type.code'));
    }

    public function test_admin_can_search_project_types_by_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $projectType = ProjectType::create(['name' => 'Aplikasi Desktop Uji']);

        $this->actingAs($admin)
            ->getJson('/api/v1/master/project-types?search='.$projectType->code)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $projectType->code);
    }
}
