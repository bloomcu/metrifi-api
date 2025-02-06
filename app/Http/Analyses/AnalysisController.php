<?php

namespace DDD\Http\Analyses;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Resources\AnalysisResource;
use DDD\Domain\Analyses\Requests\AnalysisUpdateRequest;
use DDD\Domain\Analyses\Analysis;
use DDD\Domain\Analyses\Actions\AnalyzeDashboardAction;
use DDD\App\Controllers\Controller;

class AnalysisController extends Controller
{
    public function index(Organization $organization, Dashboard $dashboard)
    {
        return AnalysisResource::collection($dashboard->analyses);
    }

    public function store(Organization $organization, Dashboard $dashboard, Request $request)
    {   
        return AnalyzeDashboardAction::run($dashboard);
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
