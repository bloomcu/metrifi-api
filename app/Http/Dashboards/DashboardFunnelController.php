<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Funnel;
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

    public function toggleStep(Organization $organization, Dashboard $dashboard, Funnel $funnel, Request $request)
    {
        // Todo: create a request for this

        $pivot = DashboardFunnel::where('dashboard_id', '=', $dashboard->id)
            ->where('funnel_id', '=', $funnel->id)
            ->firstOrFail();

        if ($pivot->disabled_steps->contains($request->step_id)) {
            // If the collection contains the number, remove it
            $pivot->disabled_steps = $pivot->disabled_steps->reject(function ($value) use ($request) {
                return $value === $request->step_id;
            })->values();
        } else {
            // If the collection doesn't contain the number, add it
            $pivot->disabled_steps->push($request->step_id);
        }

        $pivot->save();
        
        // Todo: create a resource for this
        return response()->json([
            'message' => 'Dashboard funnel step toggled successfully'
        ], 200);
    }

    public function enableSteps(Organization $organization, Dashboard $dashboard, Funnel $funnel)
    {
        // Todo: create a request for this

        $pivot = DashboardFunnel::where('dashboard_id', '=', $dashboard->id)
            ->where('funnel_id', '=', $funnel->id)
            ->firstOrFail();

        $pivot->update([
            'disabled_steps' => null
        ]);
        
        // Todo: create a resource for this
        return response()->json([
            'message' => 'Dashboard funnel steps re-enabled successfully'
        ], 200);
    }
}
