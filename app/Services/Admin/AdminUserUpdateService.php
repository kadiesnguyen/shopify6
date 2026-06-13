<?php

namespace App\Services\Admin;

use App\Models\ShippingAddress;
use App\Models\Shop;
use App\Models\ShopApplication;
use App\Models\User;
use App\Support\Storage\PublicUploadStorage;
use App\Support\Storage\ShopDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserUpdateService
{
    public function __construct(private readonly AdminShopRoleTransitionService $shopRoleTransition) {}

    /** @param  array<string, mixed>  $data */
    public function update(User $user, array $data): User
    {
        $userData = Arr::only($data, ['username', 'user_code', 'name', 'email', 'phone', 'status']);

        if (! empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        if (! empty($data['payment_password'])) {
            $userData['payment_password'] = $data['payment_password'];
        }

        $user->update($userData);

        if (isset($data['role'])) {
            $this->shopRoleTransition->beforeRoleChange($user, (string) $data['role']);

            match ($data['role']) {
                'member' => $user->syncRoles(['member']),
                'shop', 'shop_personal', 'shop_business' => $user->syncRoles(['shop', 'member']),
                default => $user->syncRoles([$data['role']]),
            };
        }

        $this->syncShippingAddress($user, $data);

        if (User::isAdminShopFormRole($data['role'] ?? null)) {
            $this->syncShopProfile($user, $data);
            $this->resolvePendingRegistrationApplications($user);
        }

        return $user->fresh(['roles', 'shop', 'wallet', 'shippingAddresses']);
    }

    private function resolvePendingRegistrationApplications(User $user): void
    {
        $reviewerId = auth()->id();

        if (! $reviewerId) {
            return;
        }

        ShopApplication::query()
            ->where('user_id', $user->id)
            ->where('status', ShopApplication::STATUS_PENDING)
            ->where('application_kind', ShopApplication::KIND_REGISTRATION)
            ->update([
                'status' => ShopApplication::STATUS_APPROVED,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);
    }

    /** @param  array<string, mixed>  $data */
    private function syncShippingAddress(User $user, array $data): void
    {
        $map = [
            'shipping_recipient_name' => 'recipient_name',
            'shipping_phone' => 'phone',
            'shipping_address' => 'address_line',
            'shipping_city' => 'city',
            'shipping_state' => 'state',
            'shipping_postal_code' => 'postal_code',
            'shipping_country' => 'country',
        ];

        $payload = [];

        foreach ($map as $inputKey => $column) {
            if (! array_key_exists($inputKey, $data)) {
                continue;
            }

            $payload[$column] = $data[$inputKey] === '' ? null : $data[$inputKey];
        }

        if ($payload === []) {
            return;
        }

        $address = $user->shippingAddresses()->where('is_default', true)->first()
            ?? $user->shippingAddresses()->first();

        if ($address) {
            foreach (['recipient_name', 'phone', 'address_line', 'country'] as $requiredColumn) {
                if (array_key_exists($requiredColumn, $payload) && $payload[$requiredColumn] === null) {
                    unset($payload[$requiredColumn]);
                }
            }

            if ($payload !== []) {
                $address->update($payload);
            }

            return;
        }

        $createPayload = [
            'user_id' => $user->id,
            'recipient_name' => $payload['recipient_name'] ?? $user->name,
            'phone' => $payload['phone'] ?? $user->phone ?? '',
            'address_line' => $payload['address_line'] ?? '',
            'city' => $payload['city'] ?? null,
            'state' => $payload['state'] ?? null,
            'postal_code' => $payload['postal_code'] ?? null,
            'country' => $payload['country'] ?? 'Việt Nam',
            'is_default' => true,
        ];

        if (! filled($createPayload['address_line'])) {
            return;
        }

        ShippingAddress::query()->create($createPayload);
    }

    /** @param  array<string, mixed>  $data */
    private function syncShopProfile(User $user, array $data): void
    {
        $shopFields = [
            'shop_name' => 'name',
            'id_number' => 'id_number',
            'address' => 'address',
            'country' => 'country',
            'followers' => 'followers',
            'credit_score' => 'credit_score',
            'star_rating' => 'star_rating',
            'display_pending_orders' => 'display_pending_orders',
            'display_delivering_orders' => 'display_delivering_orders',
            'display_received_orders' => 'display_received_orders',
            'display_completed_orders' => 'display_completed_orders',
            'display_total_income' => 'display_total_income',
            'display_balance' => 'display_balance',
            'display_total_sales' => 'display_total_sales',
            'display_total_profit' => 'display_total_profit',
            'display_orders_today' => 'display_orders_today',
            'display_sales_today' => 'display_sales_today',
            'display_profit_today' => 'display_profit_today',
            'display_visitors_today' => 'display_visitors_today',
            'display_visitors_7d' => 'display_visitors_7d',
            'display_visitors_30d' => 'display_visitors_30d',
        ];

        $shopPayload = [];

        foreach ($shopFields as $inputKey => $column) {
            if (! array_key_exists($inputKey, $data)) {
                continue;
            }

            if ($column === 'name' && ! filled($data[$inputKey])) {
                continue;
            }

            $shopPayload[$column] = $data[$inputKey] === '' ? null : $data[$inputKey];
        }

        foreach (['logo', 'id_front', 'id_back'] as $fileKey) {
            $uploadedFile = $data[$fileKey] ?? null;

            if (! $uploadedFile instanceof UploadedFile) {
                continue;
            }

            $shopPayload[$fileKey] = $this->storeShopImage($uploadedFile, $user, $fileKey);
        }

        if (isset($data['role']) && User::isAdminShopFormRole($data['role'])) {
            $shopPayload['seller_type'] = $data['role'] === 'shop_business'
                ? Shop::TYPE_BUSINESS
                : Shop::TYPE_PERSONAL;
        }

        if ($shopPayload === [] && ! $user->shop && blank($data['shop_name'] ?? null)) {
            return;
        }

        $shop = $user->shop;

        if (! $shop) {
            $shopName = filled($data['shop_name'] ?? null)
                ? (string) $data['shop_name']
                : (filled($user->name) ? $user->name : 'Shop '.$user->user_code);

            $shop = Shop::query()->create([
                'user_id' => $user->id,
                'name' => $shopName,
                'slug' => $this->uniqueShopSlug($shopName),
                'status' => Shop::STATUS_ACTIVE,
                'seller_type' => isset($data['role']) && $data['role'] === 'shop_business'
                    ? Shop::TYPE_BUSINESS
                    : Shop::TYPE_PERSONAL,
            ]);
        }

        if ($shopPayload !== []) {
            $shop->update($shopPayload);
        }
    }

    private function storeShopImage(UploadedFile $file, User $user, string $type): string
    {
        $existingPath = $user->shop?->{$type};

        if (filled($existingPath)) {
            $this->deleteStoredImage($existingPath);
        }

        if (in_array($type, ['id_front', 'id_back'], true)) {
            return ShopDocumentStorage::store($file, $user->id, $type);
        }

        return PublicUploadStorage::store($file, 'shops/'.$user->id.'/'.$type);
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! filled($path) || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (ShopDocumentStorage::isPrivatePath($path)) {
            ShopDocumentStorage::delete($path);

            return;
        }

        PublicUploadStorage::delete($path);
    }

    private function uniqueShopSlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'shop';
        $baseSlug = $slug;
        $counter = 1;

        while (Shop::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
