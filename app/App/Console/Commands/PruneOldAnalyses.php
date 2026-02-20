<?php

namespace DDD\App\Console\Commands;

use DDD\Domain\Analyses\Analysis;
use DDD\Domain\Dashboards\Dashboard;
use Illuminate\Console\Command;

class PruneOldAnalyses extends Command
{
    protected $signature = 'analyses:prune
                            {--keep=5 : Number of most recent analyses to keep per dashboard per type}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Prune old analyses, keeping only the N most recent per dashboard per type';

    public function handle(): int
    {
        $keep = (int) $this->option('keep');
        $dryRun = $this->option('dry-run');

        if ($keep < 1) {
            $this->error('The --keep option must be at least 1.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Pruning analyses (keeping %d most recent per dashboard per type)%s',
            $keep,
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $totalDeleted = 0;

        Dashboard::query()
            ->select('id')
            ->chunk(100, function ($dashboards) use ($keep, $dryRun, &$totalDeleted) {
                foreach ($dashboards as $dashboard) {
                    foreach (['median', 'max'] as $type) {
                        $idsToDelete = Analysis::query()
                            ->where('dashboard_id', $dashboard->id)
                            ->where('type', $type)
                            ->orderByDesc('created_at')
                            ->skip($keep)
                            ->limit(999999)
                            ->pluck('id');

                        if ($idsToDelete->isEmpty()) {
                            continue;
                        }

                        $count = $idsToDelete->count();
                        $totalDeleted += $count;

                        if (! $dryRun) {
                            Analysis::whereIn('id', $idsToDelete)->forceDelete();
                        }
                    }
                }
            });

        $this->info(sprintf(
            '%s %d analyses.',
            $dryRun ? 'Would delete' : 'Deleted',
            $totalDeleted
        ));

        return self::SUCCESS;
    }
}
