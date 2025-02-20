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

    public int $jobTries = 2;
    public int $jobBackoff = 120;
    public int $jobTimeout = 30;

    function handle(Dashboard $dashboard)
    { 
        // Clean up past issues and warnings
        $dashboard->update([
          'analysis_in_progress' => 1,
          'issue' => null,
          'warning' => null,
        ]);
        
        if (!$dashboard->funnels->count()) {
            $dashboard->update([
                'analysis_in_progress' => 0,
                'issue' => 'Dashboard has no funnels.'
            ]);
            return new ShowDashboardResource($dashboard);
        }

        if (count($dashboard->funnels) === 1) {
            $dashboard->update([
                'issue' => 'Dashboard has no comparison funnels.'
            ]);
        }

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

        $subjectFunnel = GoogleAnalyticsData::funnelReport(
            funnel: $dashboard->funnels[0], 
            startDate: $period['startDate'], 
            endDate: $period['endDate'],
            disabledSteps: json_decode($dashboard->funnels[0]->pivot->disabled_steps),
        );

        if (count($subjectFunnel['report']['steps']) < 2) {
            $dashboard->update([
                'issue' => 'Focus funnel has less than two steps.'
            ]);
        }

        // Build array of comparison funnel reports, only including those with matching step counts
        $comparisonFunnels = [];
        foreach ($dashboard->funnels as $key => $comparisonFunnel) {
            if ($key === 0) continue; // Skip subject funnel

            $funnel = GoogleAnalyticsData::funnelReport(
                funnel: $comparisonFunnel, 
                startDate: $period['startDate'], 
                endDate: $period['endDate'],
                disabledSteps: json_decode($comparisonFunnel->pivot->disabled_steps),
            );

            $subjectStepCount = count($subjectFunnel['report']['steps']);
            $comparisonStepCount = count($funnel['report']['steps']);

            if ($comparisonStepCount === $subjectStepCount) {
                $comparisonFunnels[] = $funnel;
                // Clear any previous issue on the pivot if it matches now
                if ($comparisonFunnel->pivot->issue) {
                    $dashboard->funnels()->updateExistingPivot($comparisonFunnel->id, [
                        'issue' => null
                    ]);
                }
            } else {
                // Record issue on the pivot table for this funnel
                $dashboard->funnels()->updateExistingPivot($comparisonFunnel->id, [
                    'issue' => "This funnel has $comparisonStepCount steps, expected $subjectStepCount to match subject funnel."
                ]);
            }
        }

        // Check comparison funnel matching status
        if (count($dashboard->funnels) > 1) { // If there are comparison funnels
          $matchingCount = count($comparisonFunnels);
          $totalComparisonCount = count($dashboard->funnels) - 1;
          
          if ($matchingCount === 0) {
              $dashboard->update([
                  'issue' => 'No comparison funnels have the same number of steps as the subject funnel.'
              ]);
          } elseif ($matchingCount < $totalComparisonCount) {
              $dashboard->update([
                  'warning' => "Only $matchingCount out of $totalComparisonCount comparison funnels have the same number of steps as the subject funnel."
              ]);
          }
      }
        
        foreach (['median', 'max'] as $analysisType) {
            $analysis = $dashboard->analyses()->create([
                'subject_funnel_id' => $dashboard->funnels[0]->id,
                'subject_funnel_conversion_value' => $dashboard->funnels[0]->conversion_value,
                'subject_funnel_assets' => $subjectFunnel['report']['assets'] * 100,
                'type' => $analysisType,
                'start_date' => now()->subDays(28),
                'end_date' => now()->subDays(1),
            ]);

            if (!empty($comparisonFunnels)) {
                Step1GetSubjectFunnelPerformance::run($analysis, $subjectFunnel, $comparisonFunnels);
                Step2GetSubjectFunnelBOFI::run($analysis, $subjectFunnel, $comparisonFunnels);
                Step3CalculatePotentialAssets::run($analysis, $subjectFunnel, $comparisonFunnels);  
            }
        }

        $dashboard->update([
            'analysis_in_progress' => 0,
        ]);

        CalculateOrganizationTotalAssetsAction::run($dashboard->organization);

        return new ShowDashboardResource($dashboard);
    }
}