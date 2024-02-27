<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Controllers\Controller;

class FunnelReplicateController extends Controller
{
    public function replicate(Organization $organization, Funnel $funnel, Request $request)
    {
        $clonedFunnel = $funnel->replicate();
        $clonedFunnel->name = $funnel->name . ' (Copy)';
        $clonedFunnel->push();
        
        // Clone each funnels step
        foreach ($funnel->steps as $step) {
            $clonedStep = $step->replicate();
            $clonedFunnel->steps()->save($clonedStep);
        }

        return new FunnelResource($clonedFunnel);
    }
}
