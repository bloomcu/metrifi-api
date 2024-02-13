<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelStepResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Jobs\GenerateFunnelStepsJob;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\GetOutboundLinksAction;
use DDD\Domain\Funnels\Actions\GetFunnelStepsAction;
use DDD\Domain\Funnels\Actions\GetFunnelEndpoints;
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
        $steps = GetFunnelStepsAction::run($funnel, $request->terminalPagePath);

        // Create funnel steps.
        foreach ($steps as $key => $pagePath) {
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

        $links = GetOutBoundLinksAction::run($funnel);

        // Create a message for the funnel
        if ($links) {
            $funnel->messages()->create([
                'type' => 'info',
                'title' => count($links) . ' outbound link(s) found',
                'json' => $links,
            ]);
        }

        return FunnelStepResource::collection($funnel->steps);
        // return new FunnelResource($funnel);
    }

    // public function generateFunnelOutboundLinksMessage(Organization $organization, Funnel $funnel)
    // {
    //     $links = GetOutBoundLinksAction::run($funnel);

    //     // Create a message for the funnel
    //     if ($links) {
    //         $funnel->messages()->create([
    //             'type' => 'info',
    //             'title' => count($links) . ' outbound link(s) found',
    //             'json' => $links,
    //         ]);
    //     }

    //     return response()->json(['data' => $funnel->messages], 200);
    // }
}
