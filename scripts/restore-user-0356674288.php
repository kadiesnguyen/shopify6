<?php

/**
 * Restore demo shop user 0356674288 from backup snapshot (2026-06-10 17:58).
 * Safe to re-run: updates password hashes and ensures shop/wallet/roles exist.
 */

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$password = '$2y$12$jWD77hZTJNYkt8nJ5hME0Oum.2Y5LT.HCN2ZKARSXV6BgLeWZP5tG';
$paymentPassword = '$2y$12$oD.x1ym481Ibh8txbXi/WOggo8MKzZ7AFPUzdlI3r3G5IuyMmom3O';

$user = User::query()->where('phone', '0356674288')->first();

if (! $user) {
    $userId = DB::table('users')->insertGetId([
        'username' => 'u0356674288',
        'user_code' => 'U000005',
        'name' => 'u0356674288',
        'email' => '0356674288@member.shopefy.local',
        'phone' => '0356674288',
        'avatar' => null,
        'password' => $password,
        'payment_password' => $paymentPassword,
        'status' => 'active',
        'distribution_locked' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $user = User::query()->findOrFail($userId);
    echo "Created user id={$userId}\n";
} else {
    DB::table('users')->where('id', $user->id)->update([
        'username' => 'u0356674288',
        'user_code' => 'U000005',
        'email' => '0356674288@member.shopefy.local',
        'password' => $password,
        'payment_password' => $paymentPassword,
        'status' => 'active',
        'updated_at' => now(),
    ]);
    echo "Updated existing user id={$user->id}\n";
}

DB::table('wallets')->updateOrInsert(
    ['user_id' => $user->id],
    [
        'balance' => 0,
        'balance_pending' => 0,
        'balance_frozen' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ],
);

$shop = $user->shop;

if (! $shop) {
    DB::table('shops')->insert([
        'user_id' => $user->id,
        'name' => 'con chim non',
        'slug' => 'con-chim-non',
        'description' => null,
        'logo' => 'shop-applications/logos/7Vvtqw7gGf3IXtDOW7Spn03QmMzP0PLqz8Ir0jtl.jpg',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
} else {
    DB::table('shops')->where('id', $shop->id)->update([
        'name' => 'con chim non',
        'slug' => 'con-chim-non',
        'logo' => 'shop-applications/logos/7Vvtqw7gGf3IXtDOW7Spn03QmMzP0PLqz8Ir0jtl.jpg',
        'status' => 'active',
        'updated_at' => now(),
    ]);
}

DB::table('shipping_addresses')->updateOrInsert(
    ['user_id' => $user->id, 'is_default' => 1],
    [
        'recipient_name' => 'nguyen vu vu',
        'phone' => '3243432',
        'address_line' => 'ho chi minh',
        'city' => 'ho chi minh',
        'state' => 'ho chi minh',
        'postal_code' => null,
        'country' => 'Việt Nam',
        'updated_at' => now(),
        'created_at' => now(),
    ],
);

Role::findOrCreate('member');
Role::findOrCreate('shop');
$user->refresh()->syncRoles(['member', 'shop']);

echo 'OK phone=0356674288 shop='.($user->shop?->name ?? 'none').' roles='.$user->roles->pluck('name')->join(',')."\n";
