<?php

namespace App\Services\Admin;

use App\Models\ShippingAddress;
use App\Models\Shop;
use App\Models\User;
use App\Support\Storage\ShopDocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AdminUserUpdateService
{
    /** @param  array<string, mixed>  $data */
    public function update(User $user, array $data): User
    {
        $userData = Arr::only($data, ['username', 'user_code', 'name', 'email', 'phone', 'status']);

        if (! empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        $user->update($userData);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            $this->syncShopRole($user, $data['role']);
        }

        $this->syncShippingAddress($user, $data);
        $this->syncShopProfile($user, $data);

        return $user->fresh(['roles', 'shop', 'wallet', 'shippingAddresses']);
    }

    /** @param  array<string, mixed>  $data */
    private function syncShippingAddress(User $user, array $data): void
    {
        $addressLine = $data['address'] ?? null;
        $country = $data['country'] ?? null;

        if ($addressLine === null && $country === null) {
            return;
        }

        $address = $user->shippingAddresses()->where('is_default', true)->first()
            ?? $user->shippingAddresses()->first();

        if ($address) {
            $address->update([
                'address_line' => $addressLine ?? $address->address_line,
                'country' => $country ?? $address->country,
            ]);

            return;
        }

        if (filled($addressLine) || filled($country)) {
            ShippingAddress::query()->create([
                'user_id' => $user->id,
                'recipient_name' => $user->name,
                'phone' => $user->phone,
                'address_line' => $addressLine,
                'country' => $country,
                'is_default' => true,
            ]);
        }
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
            if (array_key_exists($inputKey, $data)) {
                $shopPayload[$column] = $data[$inputKey] === '' ? null : $data[$inputKey];
            }
        }

        foreach (['logo', 'id_front', 'id_back'] as $fileKey) {
            $uploadedFile = $data[$fileKey] ?? null;

            if (! $uploadedFile instanceof UploadedFile) {
                continue;
            }

            $shopPayload[$fileKey] = $this->storeShopImage($uploadedFile, $user, $fileKey);
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

        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
        $filename = now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $relativeDir = 'uploads/shops/'.$user->id.'/'.$type;
        $absoluteDir = public_path($relativeDir);

        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $file->move($absoluteDir, $filename);

        return $relativeDir.'/'.$filename;
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

        if (str_starts_with($path, 'uploads/')) {
            $fullPath = public_path($path);

            if (is_file($fullPath)) {
                unlink($fullPath);
            }

            return;
        }

        Storage::disk('public')->delete($path);
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

    private function syncShopRole(User $user, string $role): void
    {
        if ($role !== 'shop') {
            return;
        }

        Role::findOrCreate('shop');
    }
}
