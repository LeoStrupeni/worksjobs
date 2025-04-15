<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Job;
use Faker\Factory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Job::factory()->count(50)->create();
        // Client::factory()->count(50)->create();
        // \App\Models\User::factory(10)->create();
        $this->call([
            // RoleSeeder::class,
            // UserSeeder::class,
            // CountrySeeder::class,
            // PermissionSeeder::class
        ]);
    }
}
