<?php

namespace DDD\Domain\Organizations\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
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

        // Prevent duplicate sends within 24 hours using cache lock
        $cacheKey = "weekly-analysis-email-sent-{$organization->id}";
        if (Cache::has($cacheKey)) {
            // Email was already sent recently, skip
            return;
        }

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

        // Set cache lock to prevent duplicate sends for 24 hours
        Cache::put($cacheKey, true, now()->addHours(24));

        // For testing
        // Sleep::for(1)->seconds();
        // Mail::to(['ryan@bloomcu.com', 'derik@bloomcu.com'])->later(now()->addSeconds(1), new WeeklyAnalysisEmail($period, $organization, $dashboards->toArray()));
    }
}
