<?php

namespace DDD\Http\Admin;

use Illuminate\Http\Request;
use DDD\Domain\Dashboards\Resources\DashboardResource;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Actions\RunAnalysisAction;
use DDD\App\Controllers\Controller;

class AdminDashboardController extends Controller
{
    /**
     * List dashboards across all organizations.
     */
    public function index()
    {
        $dashboards = Dashboard::all();
        
        return DashboardResource::collection($dashboards);
    }

    /**
     * Analyze all dashboards
     */
    public function analyzeAll()
    {
        $dashboards = Dashboard::all();

        foreach ($dashboards as $dashboard) {
            $dashboard->update([
                'analysis_in_progress' => 1,
            ]);
            
            RunAnalysisAction::dispatch($dashboard);
        }
        
        return DashboardResource::collection($dashboards);
    }
}
