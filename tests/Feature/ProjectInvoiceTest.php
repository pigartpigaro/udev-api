<?php

namespace Tests\Feature;

use App\Models\Master\Customer;
use App\Models\Master\ProjectType;
use App\Models\Projects\Project;
use App\Models\Projects\ProjectInvoice;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProjectInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_can_create_monthly_invoices_and_issue_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->makeProject();
        $response = $this->actingAs($admin)->postJson('/api/v1/invoices', [
            'project_id' => $project->id,
            'billing_cycle' => 'monthly',
            'billing_period' => '2026-10',
            'amount' => '1250000.00',
            'issued_at' => '2026-10-02',
            'due_at' => '2026-10-30',
            'notes' => 'Tahap awal project',
        ]);

        $response->assertCreated()->assertJsonPath('invoice.project_id', $project->id)->assertJsonPath('invoice.status', 'draft');
        $this->assertMatchesRegularExpression('/^U-IN-\d{4}-\d{4,}$/', $response->json('invoice.code'));

        $this->actingAs($admin)->postJson('/api/v1/invoices/'.$response->json('invoice.id').'/issue')->assertOk()->assertJsonPath('invoice.status', 'issued');
        $this->actingAs($admin)->postJson('/api/v1/invoices', ['project_id' => $project->id, 'billing_cycle' => 'monthly', 'billing_period' => '2026-10', 'amount' => '100', 'issued_at' => '2026-10-02'])->assertUnprocessable();
        $this->actingAs($admin)->postJson('/api/v1/invoices', ['project_id' => $project->id, 'billing_cycle' => 'monthly', 'billing_period' => '2026-11', 'amount' => '100', 'issued_at' => '2026-11-02'])->assertCreated()->assertJsonPath('invoice.billing_period', '2026-11');
    }

    public function test_issued_invoice_cannot_be_edited_and_can_be_cancelled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = ProjectInvoice::create(['project_id' => $this->makeProject()->id, 'billing_period' => '2026-10', 'amount' => '500.00', 'issued_at' => '2026-10-02']);
        $invoice->update(['status' => 'issued']);

        $this->actingAs($admin)->patchJson('/api/v1/invoices/'.$invoice->id, ['amount' => '600.00'])->assertConflict();
        $this->actingAs($admin)->postJson('/api/v1/invoices/'.$invoice->id.'/cancel', ['reason' => 'Nominal invoice salah'])->assertOk()->assertJsonPath('invoice.status', 'cancelled')->assertJsonPath('invoice.cancellation_reason', 'Nominal invoice salah');
        $replacement = $this->actingAs($admin)->postJson('/api/v1/invoices', [
            'project_id' => $invoice->project_id, 'billing_cycle' => 'monthly', 'billing_period' => '2026-10', 'supersedes_invoice_id' => $invoice->id,
            'amount' => '600.00', 'issued_at' => '2026-10-02',
        ]);
        $replacement->assertCreated()->assertJsonPath('invoice.supersedes_invoice_id', $invoice->id);
    }

    public function test_one_time_invoice_does_not_require_a_billing_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = $this->makeProject();
        $payload = ['project_id' => $project->id, 'billing_cycle' => 'one_time', 'billing_period' => null, 'amount' => '2500000', 'issued_at' => '2026-10-03'];

        $this->actingAs($admin)->postJson('/api/v1/invoices', $payload)
            ->assertCreated()->assertJsonPath('invoice.billing_cycle', 'one_time')->assertJsonPath('invoice.billing_period', null);
        $this->actingAs($admin)->postJson('/api/v1/invoices', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('billing_cycle');
    }

    private function makeProject(): Project
    {
        $customer = Customer::create(['name' => 'Pelanggan Invoice Test']);
        $type = ProjectType::create(['name' => 'Jenis Invoice Test']);
        return Project::create(['name' => 'Project Invoice Test', 'customer_id' => $customer->id, 'project_type_id' => $type->id]);
    }
}
