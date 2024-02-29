<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Funnels\Funnel;

class FunnelsSeeder extends Seeder
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
                'name' => 'The funnel name',
                'description' => 'The funnel description',
                'zoom' => 0,
            ],
        ];

        foreach ($funnels as $funnel) {
            Funnel::create($funnel);
        }
    }
}
