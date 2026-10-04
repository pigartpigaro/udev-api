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

class ProjectPaymentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_invoice_accepts_multiple_partial_payments_and_prevents_overpayment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->makeIssuedInvoice('1000.00');

        $first = $this->actingAs($admin)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id, 'payment_type' => 'down_payment', 'amount' => '250.00',
            'received_at' => '2026-10-03', 'method' => 'bank_transfer', 'reference' => 'TRX-001',
        ]);
        $first->assertCreated()->assertJsonPath('payment.status', 'received')->assertJsonPath('payment.payment_type', 'down_payment');
        $this->assertMatchesRegularExpression('/^U-PY-\d{4}-\d{6,}$/', $first->json('payment.code'));
        $this->actingAs($admin)->getJson('/api/v1/payments/eligible-invoices?search='.$invoice->code)
            ->assertOk()->assertJsonPath('data.0.id', $invoice->id)->assertJsonPath('data.0.remaining_amount', '750.00');
        $this->actingAs($admin)->getJson('/api/v1/payments/'.$first->json('payment.id'))
            ->assertOk()->assertJsonPath('payment.invoice.project.customer.name', 'Pelanggan Pembayaran Test');

        $this->actingAs($admin)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id, 'payment_type' => 'installment', 'amount' => '750.01',
            'received_at' => '2026-10-04', 'method' => 'cash',
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');

        $this->actingAs($admin)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id, 'payment_type' => 'final', 'amount' => '750.00',
            'received_at' => '2026-10-04', 'method' => 'cash',
        ])->assertCreated()->assertJsonPath('payment.payment_type', 'final');

        $this->actingAs($admin)->getJson('/api/v1/payments/eligible-invoices?search='.$invoice->code)
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_payment_details_are_available_for_receipt_generation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->makeIssuedInvoice('750.00');
        $payment = $this->actingAs($admin)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id, 'payment_type' => 'down_payment', 'amount' => '250.00',
            'received_at' => '2026-10-03', 'method' => 'bank_transfer', 'reference' => 'TRX-KWT-01',
        ])->assertCreated()->json('payment');

        $this->actingAs($admin)->getJson('/api/v1/payments/'.$payment['id'])
            ->assertOk()
            ->assertJsonPath('payment.code', $payment['code'])
            ->assertJsonPath('payment.invoice.project.customer.name', 'Pelanggan Pembayaran Test')
            ->assertJsonPath('payment.reference', 'TRX-KWT-01');
    }

    public function test_received_payment_must_be_voided_before_invoice_can_be_cancelled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->makeIssuedInvoice('500.00');
        $payment = $this->actingAs($admin)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id, 'payment_type' => 'down_payment', 'amount' => '200.00',
            'received_at' => '2026-10-03', 'method' => 'other',
        ])->assertCreated()->json('payment');

        $this->actingAs($admin)->postJson('/api/v1/invoices/'.$invoice->id.'/cancel', ['reason' => 'Invoice perlu dikoreksi'])
            ->assertUnprocessable()->assertJsonValidationErrors('invoice');

        $this->actingAs($admin)->postJson('/api/v1/payments/'.$payment['id'].'/void', ['reason' => 'Pembayaran tercatat ganda'])
            ->assertOk()->assertJsonPath('payment.status', 'voided')->assertJsonPath('payment.void_reason', 'Pembayaran tercatat ganda');

        $this->actingAs($admin)->getJson('/api/v1/payments/eligible-invoices')
            ->assertOk()->assertJsonPath('data.0.remaining_amount', '500.00');
        $this->actingAs($admin)->postJson('/api/v1/invoices/'.$invoice->id.'/cancel', ['reason' => 'Invoice perlu dikoreksi'])
            ->assertOk()->assertJsonPath('invoice.status', 'cancelled');
    }

    private function makeIssuedInvoice(string $amount): ProjectInvoice
    {
        $customer = Customer::create(['name' => 'Pelanggan Pembayaran Test']);
        $type = ProjectType::create(['name' => 'Jenis Pembayaran Test']);
        $project = Project::create(['name' => 'Project Pembayaran Test', 'customer_id' => $customer->id, 'project_type_id' => $type->id]);
        return ProjectInvoice::create(['project_id' => $project->id, 'billing_period' => '2026-10', 'amount' => $amount, 'issued_at' => '2026-10-03', 'status' => 'issued']);
    }
}
