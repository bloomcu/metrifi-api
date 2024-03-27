<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DDD\Domain\Funnels\FunnelStep;

class LocalFunnelStepsSeeder extends Seeder
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
                'name' => 'Home loans',
                'metrics' => [
                    [
                        'metric' => 'pageUsers',
                        'pagePath' => '/loans/home-loans/',
                    ],
                ],
            ],
            [
                'funnel_id' => 1,
                'order' => 2,
                'name' => 'Home equity loan',
                'metrics' => [
                    [
                        'metric' => 'pageUsers',
                        'pagePath' => '/loans/home-loans/home-equity-loans/',
                    ],
                ],
            ],
            [
                'funnel_id' => 1,
                'order' => 3,
                'name' => 'Home equity application',
                'metrics' => [
                    [
                        'metric' => 'pageUsers',
                        'pagePath' => '/loans/home-loans/home-equity-loans/application/',
                    ],
                ],
            ],
        ];

        foreach ($steps as $step) {
            FunnelStep::create($step);
        }
    }
}
