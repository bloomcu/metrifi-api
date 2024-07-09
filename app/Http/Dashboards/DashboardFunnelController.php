<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Controllers\Controller;

class DashboardFunnelController extends Controller
{
    public function attach(Organization $organization, Dashboard $dashboard, Request $request)
    {
        // $funnel = Funnel::findOrFail($request->funnel_id);

        // $dashboard->funnels()->attach($request->funnel_id, [
        //     'order' => $request->order,
        // ]);

        // return new FunnelResource($funnel);

        $dashboard->funnels()->syncWithoutDetaching($request->funnel_ids);

        // $dashboard->funnels()->updateExistingPivot($funnel->id, ['order' => 1]);

        return response()->json([
            'message' => 'Funnel(s) attached to dashboard successfully'
        ], 200);
    }

    public function detach(Organization $organization, Dashboard $dashboard, Request $request)
    {
        // $funnel = Funnel::findOrFail($request->funnel_id);

        // return new FunnelResource($funnel);

        $dashboard->funnels()->detach($request->funnel_id);

        return response()->json([
            'message' => 'Funnel detached from dashboard successfully'
        ], 200);
    }
}
