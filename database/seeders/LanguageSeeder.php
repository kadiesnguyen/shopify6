<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::query()->upsert([
            ['code' => 'vi', 'name' => 'Tiếng Việt', 'is_default' => true, 'is_active' => true],
            ['code' => 'en', 'name' => 'English', 'is_default' => false, 'is_active' => true],
        ], ['code'], ['name', 'is_default', 'is_active']);
    }
}
