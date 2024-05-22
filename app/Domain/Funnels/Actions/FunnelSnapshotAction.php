<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class FunnelSnapshotAction
{
    use AsAction;

    function handle(Funnel $funnel, string $period = 'last7Days')
    {
        // Bail early if funnel has no steps yet
        if (count($funnel->steps) === 0) {
            return;
        }

        $p = match ($period) {
            'yesterday' => [
                'startDate' => now()->subDays(1)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last7Days' => [
                'startDate' => now()->subDays(7)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last28Days' => [
                'startDate' => now()->subDays(28)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ]
        };

        $report = GoogleAnalyticsData::funnelReport(
            connection: $funnel->connection, 
            startDate: $p['startDate'], 
            endDate: $p['endDate'],
            steps: $funnel->steps->toArray(),
        );

        // update funnel snapshot
        $snapshots = $funnel->snapshots;
        $snapshots[$period]['conversionRate'] = $report['overallConversionRate'];
        $funnel->snapshots = $snapshots;
        $funnel->save();
    }
}
