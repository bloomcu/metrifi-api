<?php

namespace DDD\Http\Funnels;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\ValidatePagePathsAction;
use DDD\Domain\Funnels\Actions\SegmentTerminalPagePathAction;
use DDD\App\Controllers\Controller;

class FunnelAutomationsController extends Controller
{
    public function segmentTerminalPagePath(Organization $organization, Request $request)
    {
        $response = SegmentTerminalPagePathAction::run($request->terminalPagePath);

        return $response->data;
    }

    public function validatePagePaths(Organization $organization, Funnel $funnel, Request $request)
    {
        $response = ValidatePagePathsAction::run($funnel, $request->pagePaths);

        return $response->data;

        // foreach ($response->data->pagePaths as $pagePath) {
        //     $funnel->steps()->create([
        //         'metric' => 'pageViews',
        //         'name' => $pagePath,
        //         'measurables' => [$pagePath],
        //     ]);
        // }

        // return new FunnelResource($funnel);
    }
}
