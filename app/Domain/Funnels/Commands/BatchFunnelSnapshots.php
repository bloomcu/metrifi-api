<?php

namespace DDD\Domain\Funnels\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;

class BatchFunnelSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'funnels:batch-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update snapshot for all funnels.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $periods = [
            // 'yesterday', 
            // 'last7Days', 
            'last28Days'
        ];

        $funnels = Funnel::all();
        
        foreach ($periods as $period) {
            foreach ($funnels as $funnel) {
                FunnelSnapshotAction::dispatch($funnel, $period);
            }
        }

        $this->info('Snapshot jobs dispatched.');
    }
}
