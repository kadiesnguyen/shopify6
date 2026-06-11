<?php

namespace Tests\Feature;

use App\Models\RechargeMethod;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRechargeMethodConfigTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('member');

        $this->admin = User::factory()->create(['status' => 'active']);
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_bank_recharge_method_with_bank_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.recharge-methods.store'), [
                'name' => 'Bank Transfer',
                'type' => 'bank',
                'bank_name' => 'Vietcombank',
                'bank_account_number' => '0123456789',
                'bank_account_name' => 'NGUYEN VAN A',
                'sort_order' => 1,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.recharge-methods.index'));

        $method = RechargeMethod::query()->where('name', 'Bank Transfer')->firstOrFail();

        $this->assertSame('bank', $method->type);
        $this->assertSame('Vietcombank', $method->config['bank_name'] ?? null);
        $this->assertSame('0123456789', $method->config['bank_account_number'] ?? null);
        $this->assertSame('NGUYEN VAN A', $method->config['bank_account_name'] ?? null);
    }

    public function test_admin_can_create_crypto_recharge_method_with_options(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.recharge-methods.store'), [
                'name' => 'USDT Wallet',
                'type' => 'crypto',
                'wallet_address' => 'TRX-DEMO-ADDRESS',
                'currencies' => 'USDT, BTC',
                'networks' => ['TRC20', 'ERC20'],
                'sort_order' => 2,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.recharge-methods.index'));

        $method = RechargeMethod::query()->where('name', 'USDT Wallet')->firstOrFail();

        $this->assertSame(['USDT', 'BTC'], $method->config['currencies'] ?? []);
        $this->assertCount(2, $method->config['networks'] ?? []);
        $this->assertSame('TRC20', $method->config['networks'][0]['label'] ?? null);
        $this->assertSame('TRX-DEMO-ADDRESS', $method->config['networks'][0]['wallet_address'] ?? null);
    }

    public function test_member_recharge_page_shows_method_name_without_image(): void
    {
        RechargeMethod::query()->create([
            'name' => 'Bank QR',
            'type' => 'bank',
            'status' => 'active',
            'sort_order' => 1,
            'config' => [
                'bank_name' => 'ACB',
                'bank_account_number' => '123',
                'bank_account_name' => 'TEST',
            ],
        ]);

        $member = User::factory()->create(['status' => 'active']);
        $member->assignRole('member');
        Wallet::query()->create([
            'user_id' => $member->id,
            'balance' => 100,
            'balance_pending' => 0,
            'balance_frozen' => 0,
        ]);

        $this->actingAs($member)
            ->get(route('member.wallet.recharge'))
            ->assertOk()
            ->assertSee('Bank QR')
            ->assertDontSee('image_url', false);
    }
}

