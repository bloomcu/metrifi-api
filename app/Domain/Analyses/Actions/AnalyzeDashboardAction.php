<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Dashboards\Resources\ShowDashboardResource;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Actions\Step3CalculatePotentialAssets;
use DDD\Domain\Analyses\Actions\Step2GetSubjectFunnelBOFI;
use DDD\Domain\Analyses\Actions\Step1GetSubjectFunnelPerformance;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\Domain\Organizations\Actions\CalculateOrganizationTotalAssetsAction;

class AnalyzeDashboardAction
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
        // Bail early if dashboard has no subject funnel
        if (!$dashboard->funnels->count()) {
            $dashboard->update([
                'analysis_in_progress' => 0,
                'issue' => 'Dashboard has no funnels.'
            ]);
            return new ShowDashboardResource($dashboard);
        }

        // Bail early if dashboard has no comparison funnels
        if (count($dashboard->funnels) === 1) {
            $dashboard->update([
                'analysis_in_progress' => 0,
                'issue' => 'Dashboard has no comparison funnels.'
            ]);
            return new ShowDashboardResource($dashboard);
        }

        // Setup time period (TODO: accept this as a parameter from the request)
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

        // Get subject funnel report
        $subjectFunnel = GoogleAnalyticsData::funnelReport(
            funnel: $dashboard->funnels[0], 
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

        // Bail early if subject funnel has less than 2 steps
        // We learn this after removing user hidden funnels in the report 
        if (count($subjectFunnel['report']['steps']) < 2) {
            $dashboard->update([
                'analysis_in_progress' => 0,
                'issue' => 'Focus funnel has less than two steps.'
            ]);
            return new ShowDashboardResource($dashboard);
        }

        // Bail early if all comparison funnels do not have the same number of steps as subject funnel
        foreach ($comparisonFunnels as $comparisonFunnel) {
            if (count($comparisonFunnel['report']['steps']) !== count($subjectFunnel['report']['steps'])) {
                $dashboard->update([
                    'analysis_in_progress' => 0,
                    'issue' => 'One or more funnels do not have the same number of steps.'
                ]);
                return new ShowDashboardResource($dashboard);
            }
        }
        
        foreach (['median', 'max'] as $analysisType) {
            // Create a fresh analysis
            $analysis = $dashboard->analyses()->create([
                'subject_funnel_id' => $dashboard->funnels[0]->id,
                'type' => $analysisType,
                'start_date' => now()->subDays(28), // 28 days ago
                'end_date' => now()->subDays(1), // yesterday
            ]);

            // Analyze
            Step1GetSubjectFunnelPerformance::run($analysis, $subjectFunnel, $comparisonFunnels);
            Step2GetSubjectFunnelBOFI::run($analysis, $subjectFunnel, $comparisonFunnels);
            Step3CalculatePotentialAssets::run($analysis, $subjectFunnel, $comparisonFunnels);
        }

        $dashboard->update([
            'analysis_in_progress' => 0,
        ]);

        CalculateOrganizationTotalAssetsAction::run($dashboard->organization);

        return new ShowDashboardResource($dashboard);
    }
}
