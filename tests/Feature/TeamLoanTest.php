<?php

namespace Tests\Feature;

use App\Models\Master\Customer;
use App\Models\Master\ProjectType;
use App\Models\Projects\Project;
use App\Models\Projects\ProjectInvoice;
use App\Models\Projects\ProjectPayment;
use App\Models\User;
use App\Services\Finance\WorkspaceCashPosition;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TeamLoanTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_team_loan_can_be_repaid_in_installments_and_shows_remaining_balance_by_member(): void
    {
        $baseline = app(WorkspaceCashPosition::class)->currentCents();
        [$admin, $member] = $this->fundCash('10000000.00');
        $loan = $this->actingAs($admin)->postJson('/api/v1/team-loans', [
            'borrower_id' => $member->id, 'amount' => '300000.00', 'issued_at' => '2026-10-04',
            'purpose' => 'Transport project', 'method' => 'cash',
        ])->assertCreated()->assertJsonPath('loan.status', 'open');
        $id = $loan->json('loan.id');
        $this->assertMatchesRegularExpression('/^U-KS-\d{4}-\d{6,}$/', $loan->json('loan.code'));

        $this->actingAs($admin)->postJson("/api/v1/team-loans/{$id}/repayments", ['amount' => '100000', 'repaid_at' => '2026-10-04', 'method' => 'cash'])
            ->assertCreated()->assertJsonPath('repayment.amount', '100000.00');
        $this->actingAs($admin)->getJson('/api/v1/team-loans/summary')
            ->assertOk()->assertJsonPath('cash_available', WorkspaceCashPosition::amount($baseline + 980000000))->assertJsonPath('outstanding', '200000.00')
            ->assertJsonPath('borrower_balances.0.outstanding', '200000.00');

        $this->actingAs($admin)->postJson("/api/v1/team-loans/{$id}/repayments", ['amount' => '200000', 'repaid_at' => '2026-10-04', 'method' => 'bank_transfer'])
            ->assertCreated();
        $this->actingAs($admin)->getJson('/api/v1/team-loans?search='.urlencode($member->name))
            ->assertOk()->assertJsonPath('data.0.status', 'settled')->assertJsonPath('data.0.remaining_amount', '0.00');
    }

    public function test_loan_cannot_exceed_available_cash_and_members_can_use_the_module(): void
    {
        [$admin, $member] = $this->fundCash('10000000.00');
        $tooMuch = WorkspaceCashPosition::amount(app(WorkspaceCashPosition::class)->currentCents() + 100);
        $payload = ['borrower_id' => $member->id, 'amount' => $tooMuch, 'issued_at' => '2026-10-04', 'purpose' => 'Keperluan tim', 'method' => 'cash'];
        $this->actingAs($admin)->postJson('/api/v1/team-loans', $payload)->assertUnprocessable();
        $this->actingAs($member)->getJson('/api/v1/team-loans')->assertOk();
        $this->actingAs($member)->postJson('/api/v1/team-loans', [...$payload, 'amount' => '500000.00'])->assertCreated();
    }

    private function fundCash(string $amount): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $project = Project::create([
            'name' => 'Project Kasbon Test',
            'customer_id' => Customer::create(['name' => 'Pelanggan Kasbon Test'])->id,
            'project_type_id' => ProjectType::create(['name' => 'Jenis Kasbon Test'])->id,
        ]);
        $invoice = ProjectInvoice::create(['project_id' => $project->id, 'billing_cycle' => 'one_time', 'amount' => $amount, 'issued_at' => '2026-10-01', 'status' => 'issued']);
        ProjectPayment::create(['invoice_id' => $invoice->id, 'payment_type' => 'final', 'amount' => $amount, 'received_at' => '2026-10-01', 'method' => 'cash', 'status' => 'received', 'received_by' => $admin->id]);
        return [$admin, $member];
    }
}
