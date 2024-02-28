<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SubscriptionPlansSeeder::class,
            OrganizationsSeeder::class,
            UsersSeeder::class,
            ConnectionsSeeder::class,
            FunnelsSeeder::class,
            FunnelStepsSeeder::class,
            DashboardsSeeder::class,
        ]);
    }
}
