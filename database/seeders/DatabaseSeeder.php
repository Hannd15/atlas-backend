<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CustomAuthorizationSeeder::class,
            ModuleSeeder::class,
            // Keep the randomized seeder available for non-deterministic setups
            // AuthorizationSeeder::class,
        ]);
    }
}
