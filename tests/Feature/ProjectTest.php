<?php

namespace Tests\Feature;

use App\Models\Master\Customer;
use App\Models\Master\ProjectType;
use App\Models\Projects\Project;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_can_create_project_with_generated_code_and_relations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Pelanggan Project Uji']);
        $projectType = ProjectType::create(['name' => 'Jenis Project Uji']);

        $response = $this->actingAs($admin)->postJson('/api/v1/projects', [
            'name' => 'Portal Internal Uji',
            'customer_id' => $customer->id,
            'project_type_id' => $projectType->id,
            'start_date' => '2026-10-02',
            'target_end_date' => '2026-12-31',
            'status' => 'active',
            'description' => 'Project untuk pengujian API.',
            'application_url' => 'https://app.example.test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('project.name', 'Portal Internal Uji')
            ->assertJsonPath('project.customer.id', $customer->id)
            ->assertJsonPath('project.project_type.id', $projectType->id)
            ->assertJsonPath('project.status', 'active');
        $response->assertJsonPath('project.application_url', 'https://app.example.test');

        $this->assertMatchesRegularExpression('/^U-PR-\\d{4}-\\d{4,}$/', $response->json('project.code'));
        $this->assertDatabaseHas('projects', ['name' => 'Portal Internal Uji', 'customer_id' => $customer->id, 'project_type_id' => $projectType->id]);
    }

    public function test_admin_can_search_projects_by_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Pelanggan Cari Uji']);
        $projectType = ProjectType::create(['name' => 'Jenis Cari Uji']);
        $project = Project::create([
            'name' => 'Project Cari Uji',
            'customer_id' => $customer->id,
            'project_type_id' => $projectType->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/projects?search='.$project->code)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $project->code);
    }

    public function test_project_target_date_cannot_precede_start_date_on_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Pelanggan Tanggal Uji']);
        $projectType = ProjectType::create(['name' => 'Jenis Tanggal Uji']);
        $project = Project::create([
            'name' => 'Project Tanggal Uji',
            'customer_id' => $customer->id,
            'project_type_id' => $projectType->id,
            'start_date' => '2026-10-10',
            'target_end_date' => '2026-10-20',
        ]);

        $this->actingAs($admin)
            ->patchJson('/api/v1/projects/'.$project->id, ['target_end_date' => '2026-10-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_end_date');
    }

    public function test_project_application_url_can_be_updated_or_cleared(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Pelanggan URL Uji']);
        $projectType = ProjectType::create(['name' => 'Jenis URL Uji']);
        $project = Project::create([
            'name' => 'Project URL Uji',
            'customer_id' => $customer->id,
            'project_type_id' => $projectType->id,
        ]);

        $this->actingAs($admin)
            ->patchJson('/api/v1/projects/'.$project->id, ['application_url' => 'https://demo.example.test'])
            ->assertOk()
            ->assertJsonPath('project.application_url', 'https://demo.example.test');

        $this->actingAs($admin)
            ->patchJson('/api/v1/projects/'.$project->id, ['application_url' => null])
            ->assertOk()
            ->assertJsonPath('project.application_url', null);
    }

    public function test_project_application_url_only_accepts_http_or_https(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'Pelanggan URL Invalid']);
        $projectType = ProjectType::create(['name' => 'Jenis URL Invalid']);

        $this->actingAs($admin)
            ->postJson('/api/v1/projects', [
                'name' => 'Project URL Invalid',
                'customer_id' => $customer->id,
                'project_type_id' => $projectType->id,
                'application_url' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('application_url');
    }
}
