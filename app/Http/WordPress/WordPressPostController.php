<?php

namespace DDD\Http\Dashboards;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Dashboards\Resources\ShowDashboardResource;
use DDD\Domain\Dashboards\Resources\IndexDashboardResource;
use DDD\Domain\Dashboards\Requests\DashboardUpdateRequest;
use DDD\Domain\Dashboards\Dashboard;
use DDD\App\Controllers\Controller;
use DDD\App\Services\WordPress\WordPressService;

class WordPressPostController extends Controller
{
    public function store(Request $request, WordPressService $wordpressService)
    {
        return $wordpressService->createPost($request->post);
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
