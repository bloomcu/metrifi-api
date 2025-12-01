<?php

namespace DDD\Domain\Organizations\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Mail\WeeklyAnalysisEmail;

class SendWeeklyAnalysisEmailAction
{
    use AsAction;

    function handle(Organization $organization)
    {
        /**
         * This action prepares the data for the weekly analysis email
         * and sends it to users in the organization who opt
         * into weekly website analysis emails
         * 
         */

        // Use atomic cache operation to prevent race conditions
        // Cache::add() only sets if key doesn't exist AND returns true if it succeeded
        // This is atomic and prevents the race condition where two jobs both check
        // Cache::has() before either sets the cache
        $cacheKey = "weekly-analysis-email-sent-{$organization->id}";
        
        // Try to acquire the lock atomically - if another job already has it, skip
        if (!Cache::add($cacheKey, true, now()->addHours(24))) {
            // Another job already acquired the lock, skip to prevent duplicate
            Log::info("Weekly analysis email skipped for org {$organization->id} - already sent recently (cache lock exists)");
            return;
        }

        Log::info("Weekly analysis email processing for org {$organization->id} - cache lock acquired");

        // Get the organizations users
        $users = $organization->users()->get();

        // Filter users collection by settings->send_weekly_analysis_email is true
        $notifiableUsers = $users->filter(function ($user) {
            return $user['settings']['send_weekly_website_analysis'] === true;
        });

        // Early exit if there are no notifiable users
        if ($notifiableUsers->isEmpty()) {
            return;
        }
        
        // Setup the 28 day period for the email
        $startDate = now()->subDays(28)->format('M d, Y');
        $endDate = now()->subDays(1)->format('M d, Y');
        $period = "{$startDate} - {$endDate}";

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

        // No dashboards
        if ($dashboards->isEmpty()) {
            return;
        }

        // Get the emails of notifiable users
        $emails = $notifiableUsers->pluck('email')->toArray();

        // Send the emails
        foreach ($emails as $email) {
            Sleep::for(1)->seconds();
            Mail::to($email)->queue(new WeeklyAnalysisEmail($period, $organization, $dashboards->toArray()));
        }

        Log::info("Weekly analysis email sent for org {$organization->id} to " . count($emails) . " recipient(s)");

        // Note: Cache lock was already set atomically at the start of this method
        // using Cache::add() to prevent race conditions
    }
}
