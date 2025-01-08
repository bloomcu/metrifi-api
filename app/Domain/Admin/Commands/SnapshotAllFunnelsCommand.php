<?php

namespace DDD\Domain\Admin\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;

class SnapshotAllFunnelsCommand extends Command
{
    protected $signature = 'admin:snapshot-all-funnels';

    protected $description = 'Dispatch a job to snapshot each funnels metrics.';

    public function handle()
    {
        Funnel::chunk(100, function ($funnels) {
            foreach ($funnels as $funnel) {
                FunnelSnapshotAction::dispatch($funnel, 'last28Days');
                FunnelSnapshotAction::dispatch($funnel, 'last90Days');
            }
        });

        $this->info('All funnel snapshot jobs have been queued.');
    }
}
