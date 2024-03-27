<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Dashboards\Dashboard;

class LocalDashboardsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dashboards = [
            [
                'organization_id' => 1,
                'user_id' => 1,
                'name' => 'The dashboard name',
                'description' => 'The dashboard description',
                'zoom' => 0,
            ],
        ];

        foreach ($dashboards as $d) {
            $dashboard = Dashboard::create($d);
            $dashboard->funnels()->attach(1);
        }
    }
}
