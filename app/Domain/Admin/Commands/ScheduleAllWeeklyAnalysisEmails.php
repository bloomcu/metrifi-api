<?php

namespace DDD\Domain\Admin\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Dashboards\Dashboard;
use DDD\Domain\Analyses\Actions\AnalyzeDashboardAction;

class SendWeeklyAnalysisEmail extends Command
{
    protected $signature = 'admin:analyze-all-dashboards';

    protected $description = 'Dispatch a job for each dashboard to run an analysis';

    public function handle()
    {
        $dashboards = Dashboard::all();

        foreach ($dashboards as $dashboard) {
            $dashboard->update([
                'analysis_in_progress' => 1,
            ]);
            
            AnalyzeDashboardAction::dispatch($dashboard);
        }

        $this->info('All dashboards have been queued for analysis.');
    }
}
