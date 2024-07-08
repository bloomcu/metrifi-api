<?php

namespace DDD\Http\Analyses;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Resources\AnalysisResource;
use DDD\Domain\Analyses\Analysis;
use DDD\Domain\Analyses\Actions\RunAnalysisAction;
use DDD\Domain\Analyses\Actions\AnalyzeConversionRate;
use DDD\Domain\Analyses\Actions\AnalyzeBiggestOpportunity;
use DDD\App\Controllers\Controller;

class AnalysisController extends Controller
{
    public function index(Organization $organization, Dashboard $dashboard)
    {
        return AnalysisResource::collection($dashboard->analyses);
    }

    public function store(Organization $organization, Dashboard $dashboard, Request $request)
    {
        $analysis = $dashboard->analyses()->create([
            'subject_funnel_id' => $request->subjectFunnelId,
            'in_progress' => 1,
        ]);

        AnalyzeConversionRate::run($analysis);
        AnalyzeBiggestOpportunity::run($analysis);

        return new AnalysisResource($analysis);
    }

    public function show(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    {
        return new AnalysisResource($analysis);
    }

    // public function update(Organization $organization, Dashboard $dashboard, Analysis $analysis, AnalysisUpdateRequest $request)
    // {
    //     $analysis->update($request->validated());

    //     return new AnalysisResource($analysis);
    // }

    // public function destroy(Organization $organization, Dashboard $dashboard, Analysis $analysis)
    // {
    //     $analysis->delete();

    //     return new AnalysisResource($analysis);
    // }
}
