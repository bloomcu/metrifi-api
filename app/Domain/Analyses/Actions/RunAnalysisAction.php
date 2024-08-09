<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Resources\AnalysisResource;
use DDD\Domain\Analyses\Actions\Step2GetSubjectFunnelBOFI;
use DDD\Domain\Analyses\Actions\Step1GetSubjectFunnelPerformance;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class RunAnalysisAction
{
    use AsAction;

    /**
     * Locally run:
     * php artisan queue:work --sleep=3 --tries=2 --backoff=30
     */

    public int $jobTries = 2; // number of times the job may be attempted
    public int $jobBackoff = 30; // number of seconds to wait before retrying 

    function handle(Dashboard $dashboard)
    {
        // Setup time period (later accrept this as a parameter from the request)
        $period = match ('last28Days') {
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

        // Bail early if dashboard has no subject funnel
        if (!$dashboard->funnels) {
            // Create a new analysis
            $analysis = $dashboard->analyses()->create([
                'subject_funnel_id' => null,
                'issue' => 'Dashboard has no subject funnel',
                'start_date' => now()->subDays(28), // 28 days ago
                'end_date' => now()->subDays(1), // yesterday
            ]);

            $dashboard->update([
                'analysis_in_progress' => 0,
            ]);

            return new AnalysisResource($analysis);
        }

        // Create a new analysis
        $analysis = $dashboard->analyses()->create([
            'subject_funnel_id' => $dashboard->funnels[0]->id,
            'start_date' => now()->subDays(28), // 28 days ago
            'end_date' => now()->subDays(1), // yesterday
        ]);

        // Bail early if dashboard has no funnels
        if (count($dashboard->funnels) === 1) {
            $analysis->update([
                'issue' => 'Dashboard has no comparison funnels.'
            ]);

            $dashboard->update([
                'analysis_in_progress' => 0,
            ]);

            return new AnalysisResource($analysis);
        }

        // Bail early if subject funnel has no steps
        if (count($analysis->subjectFunnel->steps) === 0) {
            $analysis->update([
                'issue' => 'Subject funnel has no steps.'
            ]);

            $dashboard->update([
                'analysis_in_progress' => 0,
            ]);

            return new AnalysisResource($analysis);
        }

        // Get subject funnel report
        $subjectFunnel = GoogleAnalyticsData::funnelReport(
            funnel: $analysis->subjectFunnel, 
            startDate: $period['startDate'], 
            endDate: $period['endDate'],
            disabledSteps: json_decode($dashboard->funnels[0]->pivot->disabled_steps),
        );

        // Build array of comparison funnel reports
        $comparisonFunnels = [];
        foreach ($dashboard->funnels as $key => $comparisonFunnel) {
            if ($key === 0) continue; // Skip subject funnel (already processed above)

            $funnel = GoogleAnalyticsData::funnelReport(
                funnel: $comparisonFunnel, 
                startDate: $period['startDate'], 
                endDate: $period['endDate'],
                disabledSteps: json_decode($comparisonFunnel->pivot->disabled_steps),
            );

            array_push($comparisonFunnels, $funnel);
        }

        Step1GetSubjectFunnelPerformance::run($analysis, $subjectFunnel, $comparisonFunnels);
        Step2GetSubjectFunnelBOFI::run($analysis, $subjectFunnel, $comparisonFunnels);

        $dashboard->update([
            'analysis_in_progress' => 0,
        ]);

        return new AnalysisResource($analysis);
    }
}
