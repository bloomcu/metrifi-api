<?php

namespace DDD\Http\Admin;

use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Actions\CalculateOrganizationTotalAssetsAction;
use DDD\Domain\Dashboards\Resources\IndexDashboardResource;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Actions\AnalyzeDashboardAction;
use DDD\App\Controllers\Controller;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $dashboards = Dashboard::query()
            ->with(['organization.subscriptions', 'medianAnalysis', 'maxAnalysis'])
            ->withCount('allFunnels as funnels_count')
            ->paginate(50);

        return IndexDashboardResource::collection($dashboards);
    }

    public function analyzeAll()
    {
        $dashboards = Dashboard::query()
            ->with(['organization.subscriptions', 'medianAnalysis', 'maxAnalysis'])
            ->withCount('allFunnels as funnels_count')
            ->get();

        foreach ($dashboards as $dashboard) {
            $dashboard->update([
                'analysis_in_progress' => 1,
            ]);

            AnalyzeDashboardAction::dispatch($dashboard);
        }

        $organizations = Organization::all();

        foreach ($organizations as $organization) {
            CalculateOrganizationTotalAssetsAction::dispatch($organization);
        }
        
        return IndexDashboardResource::collection($dashboards);
    }
}
