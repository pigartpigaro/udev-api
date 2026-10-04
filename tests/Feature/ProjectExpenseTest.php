<?php

namespace Tests\Feature;

use App\Models\Master\Customer;
use App\Models\Master\ProjectType;
use App\Models\Projects\Project;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProjectExpenseTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_it_records_project_and_team_expenses_and_keeps_voided_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::create([
            'name' => 'Project Pengeluaran Test',
            'customer_id' => Customer::create(['name' => 'Pelanggan Pengeluaran Test'])->id,
            'project_type_id' => ProjectType::create(['name' => 'Jenis Pengeluaran Test'])->id,
        ]);

        $expense = $this->actingAs($admin)->postJson('/api/v1/expenses', [
            'project_id' => $project->id,
            'category' => 'transport',
            'paid_to' => 'Vendor transport',
            'amount' => '125000.50',
            'spent_at' => '2026-10-03',
            'method' => 'cash',
            'reference' => 'KWT-001',
        ])->assertCreated()->assertJsonPath('expense.status', 'paid')->assertJsonPath('expense.project.id', $project->id);

        $this->assertMatchesRegularExpression('/^U-PG-\d{4}-\d{6,}$/', $expense->json('expense.code'));

        $teamExpense = $this->actingAs($admin)->postJson('/api/v1/expenses', [
            'category' => 'meals', 'paid_to' => 'Konsumsi rapat', 'amount' => '50000',
            'spent_at' => '2026-10-03', 'method' => 'bank_transfer',
        ])->assertCreated()->assertJsonPath('expense.project_id', null);

        $this->actingAs($admin)->postJson('/api/v1/expenses/'.$expense->json('expense.id').'/void', ['reason' => 'Transaksi tercatat ganda'])
            ->assertOk()->assertJsonPath('expense.status', 'voided')->assertJsonPath('expense.void_reason', 'Transaksi tercatat ganda');

        $this->actingAs($admin)->getJson('/api/v1/expenses?search=Vendor')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.status', 'voided');
        $this->assertNotNull($teamExpense->json('expense.code'));
    }

    public function test_expense_requires_positive_amount_valid_category_and_admin_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $valid = ['category' => 'other', 'paid_to' => 'Vendor', 'amount' => '0', 'spent_at' => '2026-10-03', 'method' => 'cash'];

        $this->actingAs($admin)->postJson('/api/v1/expenses', $valid)->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->actingAs($admin)->postJson('/api/v1/expenses', [...$valid, 'category' => 'unknown', 'amount' => '100'])
            ->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->actingAs($member)->getJson('/api/v1/expenses')->assertForbidden();
    }
}
