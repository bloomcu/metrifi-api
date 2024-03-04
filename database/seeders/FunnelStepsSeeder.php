<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Funnels\FunnelStep;

class FunnelStepsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $steps = [
            [
                'funnel_id' => 1,
                'order' => 1,
                'name' => 'Step 1',
                'description' => 'The first step',
                'measurables' => [
                    [
                        'metric' => 'pageUsers',
                        'measurable' => '/',
                    ],
                ],
            ],
            [
                'funnel_id' => 1,
                'order' => 2,
                'name' => 'Step 2',
                'description' => 'The second step',
                'measurables' => [
                    [
                        'metric' => 'pageUsers',
                        'measurable' => '/page',
                    ],
                ],
            ],
        ];

        foreach ($steps as $step) {
            FunnelStep::create($step);
        }
    }
}
