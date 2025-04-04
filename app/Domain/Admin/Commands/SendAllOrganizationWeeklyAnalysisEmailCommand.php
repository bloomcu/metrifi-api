<?php

namespace DDD\Domain\Admin\Commands;

use Illuminate\Console\Command;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Actions\SendWeeklyAnalysisEmailAction;
use Illuminate\Support\Carbon;

class SendAllOrganizationWeeklyAnalysisEmailCommand extends Command
{
    protected $signature = 'admin:send-all-organization-weekly-analysis-email {--delay=5 : Delay in seconds between each organization email dispatch}';

    protected $description = 'Dispatch weekly dashboard analysis emails for all organizations';

    public function handle()
    {
        $organizations = Organization::all();
        $delay = $this->option('delay');

        foreach ($organizations as $index => $organization) {
            // Calculate incremental delay based on organization index
            $delayInSeconds = $index * $delay;
            
            // Dispatch the job with the calculated delay
            SendWeeklyAnalysisEmailAction::dispatch($organization)
                ->delay(now()->addSeconds($delayInSeconds));
        }

        $this->info("All organizations have been queued for sending weekly analysis emails with a {$delay} second delay between each.");
    }
}
