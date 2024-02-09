<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelStepResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\GetValidPagePaths;
use DDD\Domain\Funnels\Actions\GetEndpointSegments;
use DDD\Domain\Funnels\Actions\GetFunnelEndpoints;
use DDD\Domain\Connections\Connection;
use DDD\App\Controllers\Controller;

class FunnelGenerationController extends Controller
{
    public function generateFunnels(Organization $organization, Connection $connection, Request $request)
    {
        // Get all endpoints that funnels could be generated from.
        $action = GetFunnelEndpoints::run($connection, $request->startingPagePath);

        $funnels = [];

        foreach ($action->data->pagePaths as $terminalPagePath) {
            $funnel = $organization->funnels()->create([
                'user_id' => $request->user()->id,
                'connection_id' => $connection->id,
                'name' => $terminalPagePath,
            ]);

            array_push($funnels, $funnel);
        }

        return FunnelResource::collection($funnels);
    }

    public function generateFunnelSteps(Organization $organization, Funnel $funnel, Request $request)
    {
        // Break funnel endpoint into parts then validate the parts.
        $segments = GetEndpointSegments::run($request->terminalPagePath);
        $validated = GetValidPagePaths::run($funnel, $segments->data->pagePaths);

        foreach ($validated->data->pagePaths as $key => $pagePath) {
            $funnel->steps()->create([
                'order' => $key + 1,
                'name' => $pagePath,
                'measurables' => [
                    [
                        'metric' => 'pageViews',
                        'measurable' => $pagePath,
                    ]
                ]
            ]);
        }

        return FunnelStepResource::collection($funnel->steps);
    }
}
