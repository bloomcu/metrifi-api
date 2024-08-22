<?php

namespace DDD\Http\Admin;

use Illuminate\Http\Request;
use DDD\Domain\Dashboards\Resources\IndexDashboardResource;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Actions\AnalyzeDashboardAction;
use DDD\App\Controllers\Controller;

class AdminDashboardController extends Controller
{
    /**
     * List dashboards across all organizations.
     */
    public function index()
    {
        // $dashboards = Dashboard::all();

        $dashboards = Dashboard::query()
            ->with(['organization', 'latestAnalysis'])
            ->get();


        // return $dashboards;

        return IndexDashboardResource::collection($dashboards);
    }

    /**
     * Analyze all dashboards
     */
    public function analyzeAll()
    {
        $dashboards = Dashboard::query()
            ->with(['organization', 'latestAnalysis'])
            ->get();

        foreach ($dashboards as $dashboard) {
            $dashboard->update([
                'analysis_in_progress' => 1,
            ]);

            AnalyzeDashboardAction::dispatch($dashboard);
        }
        
        return IndexDashboardResource::collection($dashboards);
    }
}
