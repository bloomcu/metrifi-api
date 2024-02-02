<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\GenerateFunnelAction;
use DDD\Domain\Connections\Connection;
use DDD\App\Controllers\Controller;

class FunnelGenerationController extends Controller
{
    public function run(Organization $organization, Connection $connection, Request $request)
    {
        $steps = GenerateFunnelAction::run($connection, $request->terminalPagePath);
        
        return json_decode($steps);
        
        return response()->json([
            'original' => json_decode($steps),
        ]);

        // Create a new funnel

        // foreach ($steps as $step) {
        //     $funnel->steps()->create([
        //         'metric' => 'pageViews',
        //         'name' => 'The step name',
        //         'measurables' => [$step],
        //     ]);
        // }

        // return new FunnelResource($funnel);
    }

    public function check(Organization $organization, Funnel $funnel, Request $request)
    {
        
    }
}
