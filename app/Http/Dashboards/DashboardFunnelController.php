<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\DashboardFunnel;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Controllers\Controller;

class DashboardFunnelController extends Controller
{
    public function attach(Organization $organization, Dashboard $dashboard, Request $request)
    {
        $dashboard->funnels()->syncWithoutDetaching($request->funnel_ids);

        foreach($request->funnel_ids as $funnel_id) {
            $pivot = DashboardFunnel::where('dashboard_id', '=', $dashboard->id)
                ->where('funnel_id', '=', $funnel_id)
                ->firstOrFail();

            $pivot->setHighestOrderNumber();
        }

        return response()->json([
            'message' => 'Funnel(s) attached to dashboard successfully'
        ], 200);
    }

    public function detach(Organization $organization, Dashboard $dashboard, Request $request)
    {
        $dashboard->funnels()->detach($request->funnel_id);

        return response()->json([
            'message' => 'Funnel detached from dashboard successfully'
        ], 200);
    }

    public function reorder(Organization $organization, Dashboard $dashboard, Request $request)
    {
        $pivot = DashboardFunnel::where('dashboard_id', '=', $dashboard->id)
            ->where('funnel_id', '=', $request->funnel_id)
            ->firstOrFail();

        $pivot->reorder($request->order);
        
        return response()->json([
            'message' => 'Funnel reordered successfully'
        ], 200);
    }
}
