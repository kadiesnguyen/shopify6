<?php

namespace App\Services\Admin;

use App\Models\Shop;
use App\Models\ShopApplication;
use App\Models\User;
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

        if ($application->user->shop) {
            throw ValidationException::withMessages([
                'user' => __('admin.shop_applications.user_has_shop'),
            ]);
        }

        $slug = Str::slug($application->shop_name);
        $baseSlug = $slug;
        $counter = 1;

        while (Shop::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $shop = Shop::query()->create([
            'user_id' => $application->user_id,
            'name' => $application->shop_name,
            'slug' => $slug,
            'logo' => $application->logo,
            'status' => Shop::STATUS_ACTIVE,
        ]);

        $application->update([
            'status' => ShopApplication::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Role::findOrCreate('shop');
        $application->user->assignRole('shop');

        return $shop;
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
}
