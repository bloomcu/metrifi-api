<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Auth;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Actions\GenerateOutBoundLinksMessageAction;
use DDD\Domain\Funnels\Actions\GenerateFunnelStepsAction;
use DDD\Domain\Connections\Connection;

class GenerateFunnelAction
{
    use AsAction;

    function handle(Organization $organization, Connection $connection, string $terminalPagePath, int $userId = null)
    {   
        $funnel = $organization->funnels()->create([
            'user_id' => $userId ?? Auth::id(),
            'connection_id' => $connection->id,
            'name' => $terminalPagePath,
        ]);

        GenerateFunnelStepsAction::run($funnel, $terminalPagePath);
        GenerateOutBoundLinksMessageAction::run($funnel);

        return $funnel;
    }
}