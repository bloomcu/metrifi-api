<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Resources\ShowDashboardResource;
use DDD\Domain\Dashboards\Resources\IndexDashboardResource;
use DDD\Domain\Dashboards\Requests\DashboardUpdateRequest;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Controllers\Controller;

class DashboardReplicateController extends Controller
{
  public function replicate(Organization $organization, Dashboard $dashboard, Request $request)
  {
      $clonedDashboard = $dashboard->replicate();
      $clonedDashboard->name = $dashboard->name . ' (Copy)';
      $clonedDashboard->push();

      // Sync funnels
      foreach ($dashboard->funnels as $funnel) {
        $clonedDashboard->funnels()->attach($funnel->id, [
          'order' => $funnel->pivot->order,
          'disabled_steps' => $funnel->pivot->disabled_steps,
        ]);
      }

      return new IndexDashboardResource($clonedDashboard);
  }
}
