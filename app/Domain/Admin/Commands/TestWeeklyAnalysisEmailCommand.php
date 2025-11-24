<?php

namespace DDD\Domain\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Mail;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Mail\WeeklyAnalysisEmail;

class TestWeeklyAnalysisEmailCommand extends Command
{
    protected $signature = 'admin:test-weekly-analysis-email {--organization-id=2 : Organization ID to test with} {--email=ryan@bloomcu.com : Email address to send test email to}';

    protected $description = 'Send a test weekly analysis email to a specific email address for testing purposes';

    public function handle()
    {
        $organizationId = $this->option('organization-id');
        $testEmail = $this->option('email');

        $this->info("Fetching organization ID {$organizationId}...");
        $organization = Organization::find($organizationId);

        if (!$organization) {
            $this->error("Organization with ID {$organizationId} not found.");
            return 1;
        }

        $this->info("Organization found: {$organization->domain} (ID: {$organization->id})");
        
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

