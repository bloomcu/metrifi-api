<?php

namespace DDD\Http\Funnels;

use Throwable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use Illuminate\Bus\Batch;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelStepResource;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Jobs\StoreFunnelJob;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\GenerateOutBoundLinksMessageAction;
use DDD\Domain\Funnels\Actions\GenerateFunnelStepsAction;
use DDD\Domain\Funnels\Actions\GenerateFunnelEndpointsAction;
use DDD\Domain\Connections\Connection;
use DDD\App\Controllers\Controller;

class FunnelGenerationController extends Controller
{
    public function generateFunnels(Organization $organization, Connection $connection, Request $request)
    {
        // Get all endpoints that funnels could be generated from.
        $endpoints = GenerateFunnelEndpointsAction::run($connection, $request->startingPagePath);

        // Create an array of jobs
        $jobs = [];
        $max = 100;
        $count = 0;
        foreach ($endpoints as $terminalPagePath) {
            if (++$count === $max + 1) break;
            array_push($jobs, new StoreFunnelJob($organization, $connection, $terminalPagePath, request()->user()->id));
        }

        // Create a new batch instance.
        $batch = Bus::batch($jobs)->then(function (Batch $batch) use ($organization) {
            // All jobs complete successfully
            $organization->update([
                'automating' => false,
                'automation_msg' => 'Funnels have been generated from ' . $batch->totalJobs . ' endpoints.',
            ]);
        })->catch(function (Batch $batch, Throwable $e) use ($organization) {
            // First batch job that fails
            $organization->update([
                'automating' => false,
                'automation_msg' => 'Failed to generate a funnel. Exception: ' . $e->getMessage(),
            ]);
        })->dispatch();
        

        // Update organization to indicate that funnels are being generated.
        $organization->update([
            'automating' => true,
            'automation_msg' => 'Funnels are being generated from ' . count($endpoints) . ' endpoints.'
        ]);

        return response()->json([
            'data' => $endpoints,
            'message' => 'Funnels are being generated from ' . count($endpoints) . ' endpoints on batch id ' . $batch->id . '.'
        ], 202);
    }

    public function generateFunnelSteps(Organization $organization, Funnel $funnel, Request $request)
    {
        GenerateFunnelStepsAction::run($funnel, $request->terminalPagePath);

        GenerateOutBoundLinksMessageAction::run($funnel);

        return FunnelStepResource::collection($funnel->steps);
    }

    public function generateFunnelOutboundLinksMessage(Organization $organization, Funnel $funnel)
    {
        GenerateOutBoundLinksMessageAction::run($funnel);

        return new FunnelResource($funnel);
    }
}
