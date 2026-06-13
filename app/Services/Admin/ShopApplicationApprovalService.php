<?php

namespace App\Services\Admin;

use App\Models\Shop;
use App\Models\ShopApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ShopApplicationApprovalService
{
    public function approve(ShopApplication $application, User $reviewer): Shop
    {
        if ($application->status !== ShopApplication::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => __('admin.shop_applications.already_reviewed'),
            ]);
        }

        return DB::transaction(function () use ($application, $reviewer): Shop {
            $application->refresh();

            if ($application->status !== ShopApplication::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => __('admin.shop_applications.already_reviewed'),
                ]);
            }

            if ($application->isUpgrade()) {
                return $this->approveUpgrade($application, $reviewer);
            }

            return $this->approveRegistration($application, $reviewer);
        });
    }

    public function reject(ShopApplication $application, User $reviewer, ?string $note = null): void
    {
        if ($application->status !== ShopApplication::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => __('admin.shop_applications.already_reviewed'),
            ]);
        }

        $application->update([
            'status' => ShopApplication::STATUS_REJECTED,
            'admin_note' => $note,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    private function approveRegistration(ShopApplication $application, User $reviewer): Shop
    {
        $user = $application->user()->lockForUpdate()->firstOrFail();

        if ($user->shop) {
            if ($user->isShop()) {
                throw ValidationException::withMessages([
                    'user' => __('admin.shop_applications.user_has_shop'),
                ]);
            }

            return $this->reactivateOrphanShop($application, $reviewer, $user, $user->shop);
        }

        $shop = Shop::query()->create([
            'user_id' => $application->user_id,
            'seller_type' => $application->seller_type,
            'name' => $application->shop_name,
            'slug' => $this->uniqueSlug($application->shop_name),
            'logo' => $application->logo,
            'address' => $application->address,
            'country' => $application->country,
            'id_number' => $application->id_number,
            'id_front' => $application->id_front,
            'id_back' => $application->id_back,
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $this->markApproved($application, $reviewer);
        $this->assignShopRole($user);

        return $shop;
    }

    private function reactivateOrphanShop(ShopApplication $application, User $reviewer, User $user, Shop $shop): Shop
    {
        $payload = [
            'seller_type' => $application->seller_type,
            'name' => $application->shop_name,
            'logo' => $application->logo ?? $shop->logo,
            'address' => $application->address,
            'country' => $application->country,
            'id_number' => $application->id_number,
            'id_front' => $application->id_front,
            'id_back' => $application->id_back,
            'status' => Shop::STATUS_ACTIVE,
        ];

        if ($shop->name !== $application->shop_name) {
            $payload['slug'] = $this->uniqueSlug($application->shop_name, $shop->id);
        }

        $shop->update($payload);

        $this->markApproved($application, $reviewer);
        $this->assignShopRole($user);

        return $shop->fresh();
    }

    private function approveUpgrade(ShopApplication $application, User $reviewer): Shop
    {
        $shop = $application->user->shop;

        if (! $shop || ! $shop->isPersonal()) {
            throw ValidationException::withMessages([
                'user' => __('admin.shop_applications.upgrade_not_eligible'),
            ]);
        }

        if ($application->seller_type !== ShopApplication::TYPE_BUSINESS) {
            throw ValidationException::withMessages([
                'seller_type' => __('admin.shop_applications.upgrade_must_be_business'),
            ]);
        }

        $shop->update([
            'seller_type' => Shop::TYPE_BUSINESS,
            'name' => $application->shop_name,
            'logo' => $application->logo ?? $shop->logo,
            'address' => $application->address,
            'country' => $application->country,
            'id_number' => $application->id_number,
            'id_front' => $application->id_front,
            'id_back' => $application->id_back,
        ]);

        $this->markApproved($application, $reviewer);

        return $shop->fresh();
    }

    private function assignShopRole(User $user): void
    {
        Role::findOrCreate('shop');
        $user->syncRoles(['shop', 'member']);
    }

    private function markApproved(ShopApplication $application, User $reviewer): void
    {
        $application->update([
            'status' => ShopApplication::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    private function uniqueSlug(string $shopName, ?int $ignoreShopId = null): string
    {
        $slug = Str::slug($shopName);
        $baseSlug = $slug;
        $counter = 1;

        while (Shop::query()
            ->when($ignoreShopId, fn ($query) => $query->where('id', '!=', $ignoreShopId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
