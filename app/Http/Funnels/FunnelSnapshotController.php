<?php

namespace DDD\Http\Funnels;

use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;
use DDD\App\Controllers\Controller;

class FunnelSnapshotController extends Controller
{
    public function refresh(Organization $organization, Funnel $funnel)
    {
        FunnelSnapshotAction::run($funnel, 'last28Days');

        return new FunnelResource($funnel);
    }
}
