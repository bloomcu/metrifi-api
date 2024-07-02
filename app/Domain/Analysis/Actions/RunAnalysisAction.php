<?php

namespace DDD\Domain\Analysis\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analysis\Analysis;

class RunAnalysisAction
{
    use AsAction;

    function handle(Analysis $analysis, string $period = 'last28Days')
    {
        // Bail early if subject funnel has no steps
        if (count($analysis->subjectFunnel->steps) === 0) {
            return;
        }

        // Bail early if dashboard has no funnels
        if (count($analysis->dashboard->funnels) === 0) {
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

        // $report = GoogleAnalyticsData::funnelReport(
        //     connection: $funnel->connection, 
        //     startDate: $p['startDate'], 
        //     endDate: $p['endDate'],
        //     steps: $funnel->steps->toArray(),
        // );

        // // update funnel snapshot
        // $snapshots = $funnel->snapshots;
        // $snapshots[$period]['conversionRate'] = $report['overallConversionRate'];
        // $funnel->snapshots = $snapshots;
        // $funnel->save();
    }
}
