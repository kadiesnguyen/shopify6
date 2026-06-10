<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Role::findOrCreate('shop');

        User::query()
            ->whereHas('shop')
            ->each(fn (User $user) => $user->assignRole('shop'));
    }

    public function down(): void
    {
        // Roles are retained; no rollback needed.
    }
};
