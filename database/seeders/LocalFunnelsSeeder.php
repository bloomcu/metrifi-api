<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Funnels\Funnel;

class LocalFunnelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $funnels = [
            [
                'organization_id' => 1,
                'user_id' => 1,
                'connection_id' => 1,
                'name' => 'Home equity loan',
                'zoom' => 0,
                'conversion_value' => 100,
            ],
        ];

        foreach ($funnels as $funnel) {
            Funnel::create($funnel);
        }
    }
}
