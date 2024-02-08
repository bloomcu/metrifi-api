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
        $response = GenerateFunnelAction::run($connection, $request->terminalPagePath);

        $funnel = $organization->funnels()->create([
            'user_id' => $request->user()->id,
            'connection_id' => $connection->id,
            'name' => 'Generated funnel',
            'description' => 'Generated from the terminal page path: ' . $request->terminalPagePath,
        ]);

        foreach ($response->data->pagePaths as $pagePath) {
            $funnel->steps()->create([
                'name' => $pagePath,
                'measurables' => [
                    'metric' => 'pageViews',
                    'measurable' => $pagePath
                ],
            ]);
        }

        return new FunnelResource($funnel);
    }
}
