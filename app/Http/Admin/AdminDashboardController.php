<?php

namespace DDD\Http\Admin;

use Illuminate\Http\Request;
use DDD\Domain\Dashboards\Resources\DashboardResource;
use DDD\Domain\Dashboards\Dashboard;
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
}
