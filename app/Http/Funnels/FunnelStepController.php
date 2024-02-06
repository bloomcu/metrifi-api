<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelStepResource;
use DDD\Domain\Funnels\Requests\StepUpdateRequest;
use DDD\Domain\Funnels\FunnelStep;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Controllers\Controller;

class FunnelStepController extends Controller
{
    public function store(Organization $organization, Funnel $funnel, Request $request)
    {
        $step = $funnel->steps()->create([
            'metric' => $request->metric,
            'name' => $request->name,
            'description' => $request->description,
            'measurables' => $request->measurables,
        ]);

        return new FunnelStepResource($step);
    }

    public function update(Organization $organization, Funnel $funnel, FunnelStep $step, StepUpdateRequest $request)
    {        
        $step->update($request->validated());

        return new FunnelStepResource($step);
    }

    public function destroy(Organization $organization, Funnel $funnel, FunnelStep $step)
    {
        $step->delete();

        return new FunnelStepResource($step);
    }
}
