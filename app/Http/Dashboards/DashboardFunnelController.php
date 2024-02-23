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
        $funnel = Funnel::findOrFail($request->funnel_id);

        $dashboard->funnels()->attach($funnel->id);

        // $dashboard->funnels()->attach($request->funnel_id, [
        //     'order' => $request->order,
        // ]);

        return new FunnelResource($funnel);
    }

    public function detach(Organization $organization, Dashboard $dashboard, Request $request)
    {
        $funnel = Funnel::findOrFail($request->funnel_id);

        $dashboard->funnels()->detach($funnel->id);

        return new FunnelResource($funnel);
    }
}
