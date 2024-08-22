<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Resources\ShowDashboardResource;
use DDD\Domain\Dashboards\Resources\IndexDashboardResource;
use DDD\Domain\Dashboards\Requests\DashboardUpdateRequest;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(Organization $organization)
    {
        $dashboards = Dashboard::query()
            ->where('organization_id', $organization->id)
            ->with(['organization', 'latestAnalysis'])
            ->get();

        return IndexDashboardResource::collection($dashboards);
    }

    public function store(Organization $organization, Request $request)
    {
        $dashboard = $organization->dashboards()->create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return new ShowDashboardResource($dashboard);
    }

    public function show(Organization $organization, Dashboard $dashboard)
    {
        return new ShowDashboardResource($dashboard);
    }

    public function update(Organization $organization, Dashboard $dashboard, DashboardUpdateRequest $request)
    {
        $dashboard->update($request->validated());

        return new ShowDashboardResource($dashboard);
    }

    public function destroy(Organization $organization, Dashboard $dashboard)
    {
        $dashboard->delete();

        return new ShowDashboardResource($dashboard);
    }
}
