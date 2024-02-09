<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelStepResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Jobs\GenerateFunnelStepsJob;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\GetValidPagePaths;
use DDD\Domain\Funnels\Actions\GetFunnelEndpoints;
use DDD\Domain\Funnels\Actions\GetEndpointSegments;
use DDD\Domain\Connections\Connection;
use DDD\App\Controllers\Controller;

class FunnelGenerationController extends Controller
{
    public function generateFunnels(Organization $organization, Connection $connection, Request $request)
    {
        // Get all endpoints that funnels could be generated from.
        $action = GetFunnelEndpoints::run($connection, $request->startingPagePath);
        
        $max = 100;
        $count = 0;
        $funnels = [];

        foreach ($action->data->pagePaths as $terminalPagePath) {
            if (++$count === $max + 1) break;
            
            $funnel = $organization->funnels()->create([
                'user_id' => $request->user()->id,
                'connection_id' => $connection->id,
                'name' => $terminalPagePath,
                'automating' => true,
            ]);

            array_push($funnels, $funnel);

            GenerateFunnelStepsJob::dispatch($funnel, $terminalPagePath);
        }

        return FunnelResource::collection($funnels);
    }

    public function generateFunnelSteps(Organization $organization, Funnel $funnel, Request $request)
    {
        GenerateFunnelStepsJob::dispatch($funnel, $request->terminalPagePath);

        return response()->json([
            'message' => 'Funnel steps are being generated.',
        ]);
    }
}
