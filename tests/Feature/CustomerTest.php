<?php

namespace Tests\Feature;

use App\Models\Master\Customer;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_admin_can_create_a_customer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/v1/customers', [
            'name' => 'PT Contoh Pelanggan',
            'contact_name' => 'Siti Contoh',
            'email' => 'siti@example.test',
            'phone' => '081234567890',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('customer.name', 'PT Contoh Pelanggan')
            ->assertJsonPath('customer.is_active', true);

        $this->assertMatchesRegularExpression('/^U-PL\\d{4,}$/', $response->json('customer.code'));

        $this->assertDatabaseHas('customers', [
            'name' => 'PT Contoh Pelanggan',
            'email' => 'siti@example.test',
        ]);
    }

    public function test_member_cannot_access_customer_endpoints(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->getJson('/api/v1/customers')
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki izin untuk tindakan ini.');
    }

    public function test_admin_can_search_customers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Customer::create([
            'name' => 'PT Nusantara Digital',
            'email' => 'kontak@nusantara.test',
        ]);
        Customer::create([
            'name' => 'PT Lain',
            'email' => 'kontak@lain.test',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/customers?search=Nusantara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'PT Nusantara Digital');
    }

    public function test_admin_can_search_customers_by_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'PT Kode Uji']);

        $this->actingAs($admin)
            ->getJson('/api/v1/customers?search='.$customer->code)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', $customer->code);
    }
}
