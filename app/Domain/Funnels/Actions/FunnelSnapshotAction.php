<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class FunnelSnapshotAction
{
    use AsAction;

    public int $jobTries = 2; // number of times the job may be attempted
    public int $jobBackoff = 120; // number of seconds to wait before retrying
    public int $jobTimeout = 30; // number of seconds before the job should timeout

    function handle(Funnel $funnel, string $period = 'last28Days')
    {
        // Bail early if funnel has no steps yet
        if (count($funnel->steps) === 0) {
            return;
        }

        $p = match ($period) {
            'last28Days' => [
                'startDate' => now()->subDays(28)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last90Days' => [
                'startDate' => now()->subDays(90)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
        };

        $funnel = GoogleAnalyticsData::funnelReport(
            funnel: $funnel, 
            startDate: $p['startDate'], 
            endDate: $p['endDate'],
        );

        // Cache the funnel snapshots object
        $snapshots = $funnel->snapshots;

        // Update the snapshot for the given period
        $snapshots[$period]['assets'] = $funnel->report['assets'];
        $snapshots[$period]['conversion_rate'] = $funnel->report['overallConversionRate'];
        $snapshots[$period]['users'] = isset($funnel->report['steps'][0]) ? (int) $funnel->report['steps'][0]['users'] : 0;

        // Save
        unset($funnel->report);
        $funnel->snapshots = $snapshots;
        $funnel->save();
    }
}
