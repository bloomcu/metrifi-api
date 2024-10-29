<?php

namespace DDD\Http\Organizations;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Base\Subscriptions\Plans\Resources\PlanResource;
use DDD\App\Controllers\Controller;

class OrganizationSubscriptionController extends Controller
{
    public function show(Organization $organization)
    {
        if ($organization->subscribed('default')) {
            $startedAt = Carbon::createFromTimeStamp($organization->subscription('default')->asStripeSubscription()->current_period_start);
            $renewsAt = Carbon::createFromTimeStamp($organization->subscription('default')->asStripeSubscription()->current_period_end);
            
            // Get usage
            $recommendationsUsed = $organization->recommendations()
                ->whereBetween('created_at', [$startedAt, $renewsAt])
                ->where('status', 'done')
                ->count();

            return response()->json([
                'subscribed' => true,
                'plan' => new PlanResource($organization->plan),
                'started_at' => $startedAt,
                'renews_at' => $renewsAt,
                'recommendations_used' => $recommendationsUsed,
            ]);

        } else {
            // Dynamically get start and renewal date based on the organization creation day
            $dayOfMonth = $organization->created_at->format ('j');
            $startedAt = Carbon::createFromDate (now()->year, now()->month, $dayOfMonth);
            $renewsAt = intval (now()->format ('j')) < intval ($dayOfMonth) ? Carbon::createFromDate (now()->year, now()->month, $dayOfMonth) : Carbon::createFromDate (now()->year, now()->month, $dayOfMonth)->addMonths(1);

            // Get usage
            $recommendationsUsed = $organization->recommendations()
                ->whereBetween('created_at', [$startedAt, $renewsAt])
                ->where('status', 'done')
                ->count();

            return response()->json([
                'subscribed' => false,
                'plan' => new PlanResource($organization->plan),
                'started_at' => $startedAt,
                'renews_at' => $renewsAt,
                'recommendations_used' => $recommendationsUsed,
            ]);
        }
    }
}
