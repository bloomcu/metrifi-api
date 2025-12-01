<?php

namespace DDD\Domain\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Mail\WeeklyAnalysisEmail;
use DDD\Domain\Organizations\Actions\SendWeeklyAnalysisEmailAction;

class TestWeeklyAnalysisEmailCommand extends Command
{
    protected $signature = 'admin:test-weekly-analysis-email 
                            {--organization-id=2 : Organization ID to test with} 
                            {--email=ryan@bloomcu.com : Email address to send test email to}
                            {--clear-cache : Clear the cache lock before sending (for repeated testing)}
                            {--test-duplicate : Dispatch the action twice to test race condition prevention}';

    protected $description = 'Send a test weekly analysis email to a specific email address for testing purposes';

    public function handle()
    {
        $organizationId = $this->option('organization-id');
        $testEmail = $this->option('email');
        $clearCache = $this->option('clear-cache');
        $testDuplicate = $this->option('test-duplicate');

        $this->info("Fetching organization ID {$organizationId}...");
        $organization = Organization::find($organizationId);

        if (!$organization) {
            $this->error("Organization with ID {$organizationId} not found.");
            return 1;
        }

        $this->info("Organization found: {$organization->domain} (ID: {$organization->id})");
        
        // Check/clear cache lock
        $cacheKey = "weekly-analysis-email-sent-{$organization->id}";
        if (Cache::has($cacheKey)) {
            if ($clearCache) {
                Cache::forget($cacheKey);
                $this->warn("Cache lock cleared for org {$organization->id}");
            } else {
                $this->warn("⚠ Cache lock exists for org {$organization->id} - email was sent recently.");
                $this->warn("  Use --clear-cache to clear the lock and send anyway.");
            }
        } else {
            $this->info("No cache lock exists for org {$organization->id}");
        }

        // Test duplicate prevention by dispatching the action twice
        if ($testDuplicate) {
            $this->info("");
            $this->info("=== Testing Race Condition Prevention ===");
            $this->info("Dispatching SendWeeklyAnalysisEmailAction TWICE simultaneously...");
            
            // Clear cache first so we can test
            Cache::forget($cacheKey);
            
            // Dispatch two jobs at the same time (no delay)
            SendWeeklyAnalysisEmailAction::dispatch($organization);
            SendWeeklyAnalysisEmailAction::dispatch($organization);
            
            $this->info("✓ Two jobs dispatched. Check your logs - only ONE should process.");
            $this->info("  Look for: 'Weekly analysis email skipped for org {$organization->id} - already sent recently'");
            $this->info("");
            $this->info("Run 'php artisan queue:work --once' twice to process the jobs and verify.");
            
            return 0;
        }

        // Setup the 28 day period for the email
        $startDate = now()->subDays(28)->format('M d, Y');
        $endDate = now()->subDays(1)->format('M d, Y');
        $period = "{$startDate} - {$endDate}";

        $this->info("Fetching dashboards for organization...");
        
        // Get the organization's 3 dashboards with the highest potential assets on the latest median analysis
        $dashboards = $organization->dashboards()
            ->whereHas('medianAnalysis', function ($query) {
                $query->where('type', '=', 'median');
                $query->where('bofi_performance', '<', 'median');
                $query->where('subject_funnel_conversion_value', '!=', 0);
            })
            ->with(['medianAnalysis' => function ($query) {
                $query->where('type', '=', 'median')->latest();
            }])
            ->get()
            ->sortByDesc(function ($dashboard) {
                return optional($dashboard->medianAnalysis)->subject_funnel_potential_assets;
            })
            ->take(3)
            ->values(); // Reset the keys

        if ($dashboards->isEmpty()) {
            $this->warn("No dashboards found matching the criteria. Email will still be sent but may have empty dashboard data.");
        } else {
            $this->info("Found {$dashboards->count()} dashboard(s) to include in email.");
        }

        // Refresh organization to get latest assets data
        $organization->refresh();
        
        $this->info("Sending test email to {$testEmail}...");
        
        // Send the test email synchronously (not queued) so we can see immediate results
        Mail::to($testEmail)->send(new WeeklyAnalysisEmail($period, $organization, $dashboards->toArray()));

        $this->info("✓ Test email sent successfully to {$testEmail}!");
        $this->info("Organization: {$organization->domain}");
        $this->info("Period: {$period}");
        $this->info("Dashboards included: {$dashboards->count()}");
        
        if ($organization->assets && isset($organization->assets['median']['potential'])) {
            $potential = round($organization->assets['median']['potential']);
            $annualized = bcmul($potential, 13.04, 2);
            $this->info("Potential assets (annualized): $" . number_format($annualized));
        }

        return 0;
    }
}

