<?php

namespace DDD\Http\Analyses;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Resources\AnalysisResource;
use DDD\Domain\Analyses\Requests\AnalysisUpdateRequest;
use DDD\Domain\Analyses\Analysis;
use DDD\Domain\Analyses\Actions\Step1GetSubjectFunnelPerformance;
use DDD\Domain\Analyses\Actions\Step2GetSubjectFunnelBOFI;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class AnalysisController extends Controller
{
    public function index(Organization $organization, Dashboard $dashboard)
    {
        return AnalysisResource::collection($dashboard->analyses);
    }

    public function store(Organization $organization, Dashboard $dashboard, Request $request)
    {   
        // Bail early if dashboard has no funnels
        if (count($dashboard->funnels) <= 1) {
            return;
        }

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

        // Create a new analysis
        $analysis = $dashboard->analyses()->create([
            'subject_funnel_id' => $request->subjectFunnelId,
            'in_progress' => 1,
            'start_date' => now()->subDays(28), // 28 days ago
            'end_date' => now()->subDays(1), // yesterday
        ]);

        // Bail early if subject funnel has no steps
        if (count($analysis->subjectFunnel->steps) === 0) {
            return;
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

        return new AnalysisResource($analysis);
    }

    public function show(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    {
        return new AnalysisResource($analysis);
    }

    public function update(Organization $organization, Dashboard $dashboard, Analysis $analysis, AnalysisUpdateRequest $request)
    {
        $analysis->update($request->validated());

        return new AnalysisResource($analysis);
    }

    // public function destroy(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    // {
    //     $analysis->delete();

    //     return new AnalysisResource($analysis);
    // }
}
