<?php

namespace Tests\Feature;

use App\Models\Master\Customer;
use App\Models\Master\ProjectType;
use App\Models\Projects\Project;
use App\Models\Projects\ProjectExpense;
use App\Models\Projects\ProjectInvoice;
use App\Models\Projects\ProjectPayment;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProjectFinancialReportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_report_calculates_period_flows_and_current_outstanding_without_voided_transactions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::create([
            'name' => 'Project Laporan Test',
            'customer_id' => Customer::create(['name' => 'Pelanggan Laporan Test'])->id,
            'project_type_id' => ProjectType::create(['name' => 'Jenis Laporan Test'])->id,
        ]);
        $invoiceInPeriod = ProjectInvoice::create(['project_id' => $project->id, 'billing_cycle' => 'one_time', 'amount' => '1000.00', 'issued_at' => '2026-10-01', 'status' => 'issued']);
        $invoiceBeforePeriod = ProjectInvoice::create(['project_id' => $project->id, 'billing_cycle' => 'one_time', 'amount' => '500.00', 'issued_at' => '2026-09-30', 'status' => 'issued']);

        $this->recordPayment($invoiceInPeriod, $admin, '300.00', '2026-10-03', 'received');
        $this->recordPayment($invoiceInPeriod, $admin, '100.00', '2026-09-30', 'received');
        $this->recordPayment($invoiceInPeriod, $admin, '50.00', '2026-10-04', 'voided');
        $this->recordPayment($invoiceBeforePeriod, $admin, '150.00', '2026-10-03', 'received');
        ProjectExpense::create(['project_id' => $project->id, 'category' => 'transport', 'paid_to' => 'Vendor', 'amount' => '120.00', 'spent_at' => '2026-10-03', 'method' => 'cash', 'status' => 'paid', 'recorded_by' => $admin->id]);
        ProjectExpense::create(['project_id' => $project->id, 'category' => 'meals', 'paid_to' => 'Vendor', 'amount' => '20.00', 'spent_at' => '2026-10-04', 'method' => 'cash', 'status' => 'voided', 'recorded_by' => $admin->id]);
        ProjectExpense::create(['category' => 'meals', 'paid_to' => 'Operasional', 'amount' => '80.00', 'spent_at' => '2026-10-03', 'method' => 'cash', 'status' => 'paid', 'recorded_by' => $admin->id]);

        $this->actingAs($admin)->getJson('/api/v1/projects/reports/financial?project_id='.$project->id.'&date_from=2026-10-01&date_to=2026-10-31')
            ->assertOk()
            ->assertJsonPath('summary.invoice_issued', '1000.00')
            ->assertJsonPath('summary.received', '450.00')
            ->assertJsonPath('summary.project_expenses', '120.00')
            ->assertJsonPath('summary.operational_expenses', '0.00')
            ->assertJsonPath('summary.cash_flow', '330.00')
            ->assertJsonPath('summary.outstanding', '950.00')
            ->assertJsonPath('projects.data.0.outstanding', '950.00');
    }

    public function test_report_validates_date_order_and_requires_permission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)->getJson('/api/v1/projects/reports/financial?date_from=2026-10-31&date_to=2026-10-01')
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
        $this->actingAs($member)->getJson('/api/v1/projects/reports/financial')->assertForbidden();
    }

    private function recordPayment(ProjectInvoice $invoice, User $admin, string $amount, string $date, string $status): void
    {
        ProjectPayment::create([
            'invoice_id' => $invoice->id, 'payment_type' => 'installment', 'amount' => $amount,
            'received_at' => $date, 'method' => 'cash', 'status' => $status, 'received_by' => $admin->id,
        ]);
    }
}
