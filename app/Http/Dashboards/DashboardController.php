<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Resources\DashboardResource;
use DDD\Domain\Dashboards\Requests\DashboardUpdateRequest;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(Organization $organization)
    {
        return DashboardResource::collection($organization->dashboards);
    }

    public function store(Organization $organization, Request $request)
    {
        $dashboard = $organization->dashboards()->create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return new DashboardResource($dashboard);
    }

    public function show(Organization $organization, Dashboard $dashboard)
    {
        return new DashboardResource($dashboard);
    }

    public function update(Organization $organization, Dashboard $dashboard, DashboardUpdateRequest $request)
    {
        $dashboard->update($request->validated());

        return new DashboardResource($dashboard);
    }

    public function destroy(Organization $organization, Dashboard $dashboard)
    {
        $dashboard->delete();

        return new DashboardResource($dashboard);
    }
}
