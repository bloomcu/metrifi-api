<?php

namespace DDD\Domain\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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
        $dispatchedCount = 0;
        $skippedCount = 0;

        foreach ($organizations as $index => $organization) {
            // Check if email was already sent recently (within 24 hours)
            $cacheKey = "weekly-analysis-email-sent-{$organization->id}";
            if (Cache::has($cacheKey)) {
                $skippedCount++;
                continue;
            }

            // Calculate incremental delay based on organization index
            $delayInSeconds = $index * $delay;
            
            // Dispatch the job with the calculated delay
            SendWeeklyAnalysisEmailAction::dispatch($organization)
                ->delay(now()->addSeconds($delayInSeconds));
            
            $dispatchedCount++;
        }

        $this->info("Queued {$dispatchedCount} organizations for weekly analysis emails (skipped {$skippedCount} that were sent recently) with a {$delay} second delay between each.");
    }
}
