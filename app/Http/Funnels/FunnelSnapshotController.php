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
        FunnelSnapshotAction::run($funnel, 'yesterday');

        return new FunnelResource($funnel);
    }
}
