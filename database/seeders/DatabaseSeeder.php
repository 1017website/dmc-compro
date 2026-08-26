<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SiteContentTranslationSeeder::class);

        User::query()->updateOrCreate(
            ['email' => '1017website@gmail.com'],
            [
                'name' => '1017 Website Developer',
                'password' => '1017Website2020.',
                'role' => 'developer',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
