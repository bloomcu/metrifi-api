<?php

namespace DDD\Http\Organizations;

use Illuminate\Support\Facades\Mail;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Organizations\Mail\WeeklyAnalysisEmail;
use DDD\Domain\Dashboards\Resources\ShowDashboardResource;
use DDD\App\Controllers\Controller;

class OrganizationWeeklyAnalysisEmailController extends Controller
{

    /**
     * Send the weekly email
     */
    public function send(Organization $organization)
    {
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
            ->values() // Reset the keys
            ->toArray();

        // Send the email
        Mail::to('ryan@bloomcu.com')->send(new WeeklyAnalysisEmail($period, $organization, $dashboards));
        
        // Return as json, the dashboards name
        // return ShowDashboardResource::collection($dashboards);
        return $dashboards;
        return 'check your email';
    }
}
