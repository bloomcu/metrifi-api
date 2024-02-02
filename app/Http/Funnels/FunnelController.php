<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Requests\FunnelUpdateRequest;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Controllers\Controller;

class FunnelController extends Controller
{
    public function index(Organization $organization)
    {
        return FunnelResource::collection($organization->funnels);
    }

    public function store(Organization $organization, Request $request)
    {
        $funnel = $organization->funnels()->create([
            'user_id' => $request->user()->id,
            'connection_id' => $organization->connections->first()->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return new FunnelResource($funnel);
    }

    public function show(Organization $organization, Funnel $funnel)
    {
        return new FunnelResource($funnel);
    }

    public function update(Organization $organization, Funnel $funnel, FunnelUpdateRequest $request)
    {
        $funnel->update($request->validated());

        return new FunnelResource($funnel);
    }

    public function destroy(Organization $organization, Funnel $funnel)
    {
        $funnel->delete();

        return new FunnelResource($funnel);
    }
}
