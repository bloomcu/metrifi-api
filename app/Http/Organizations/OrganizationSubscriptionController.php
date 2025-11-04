<?php

namespace DDD\Http\Organizations;

use Stripe\SubscriptionSchedule;
use Stripe\Stripe;
use Illuminate\Support\Carbon;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Base\Subscriptions\Plans\Resources\PlanResource;
use DDD\Domain\Base\Subscriptions\Plans\Plan;
use DDD\App\Controllers\Controller;

class OrganizationSubscriptionController extends Controller
{
    public function __construct()
    {
        // Set the Stripe key using Cashier's configuration for when we use Stripe class directly
        Stripe::setApiKey(config('cashier.secret'));
    }

    public function show(Organization $organization)
    {
        // dd($organization->subscription('default')->asStripeSubscription()->toArray());

        if ($organization->subscribed('default')) {
            // Get the Stripe subscription object
            $subscription = $organization->subscription('default')->asStripeSubscription();

            // Get subscription period
            $startedAt = Carbon::createFromTimeStamp($subscription->current_period_start);
            $renewsAt = Carbon::createFromTimeStamp($subscription->current_period_end);
            
            // Get usage
            $recommendationsUsed = $organization->recommendations()
                ->whereBetween('created_at', [$startedAt, $renewsAt])
                ->where('status', 'done')
                ->whereHas('user', function ($query) {
                    $query->where('role', '!=', 'admin');
                })
                ->count();

            // Check for a subscription schedule
            $upcomingPlan = null;
            $upcomingPlanStartAt = null;
            if ($subscription->schedule) {
                $schedule = SubscriptionSchedule::retrieve($subscription->schedule);
                $upcomingPhase = $schedule->phases[count($schedule->phases) - 1];
                
                $upcomingPlan = Plan::where('stripe_price_id', $upcomingPhase->plans[0]->price)->value('title');
                $upcomingPlanStartAt = Carbon::createFromTimeStamp($upcomingPhase->start_date);
            }

            // Get limit (custom org limit takes precedence over plan limit)
            $recommendationsLimit = $organization->recommendations_limit 
                ?? $organization->plan->limits['recommendations'] 
                ?? null;

            return response()->json([
                'subscribed' => true,
                'plan' => new PlanResource($organization->plan),
                'started_at' => $startedAt,
                'renews_at' => $renewsAt,
                'recommendations_used' => $recommendationsUsed,
                'recommendations_limit' => $recommendationsLimit,
                'ends_at' => $organization->subscription('default')->ends_at,
                'upcoming_plan' => $upcomingPlan,
                'upcoming_plan_start_at' => $upcomingPlanStartAt,
            ]);

        } else {
            // Organization is on the free plan
            
            // Use the organization's creation date as the starting point
            $creationDate = $organization->created_at;
            
            // Calculate the current billing cycle
            $now = now();
            $yearsSinceCreation = $now->diffInYears($creationDate);
            
            // Set start date to the anniversary of creation in the current billing year
            $startedAt = $creationDate->copy()->addYears($yearsSinceCreation);
            
            // If we've passed this year's anniversary, use that as the start date
            // Otherwise use last year's anniversary
            if ($startedAt->gt($now)) {
                $startedAt = $creationDate->copy()->addYears($yearsSinceCreation - 1);
            }
            
            // Renewal date is always 12 months after the start date
            $renewsAt = $startedAt->copy()->addMonths(12);

            // Get usage
            $recommendationsUsed = $organization->recommendations()
                ->whereBetween('created_at', [$startedAt, $renewsAt])
                ->where('status', 'done')
                ->whereHas('user', function ($query) {
                    $query->where('role', '!=', 'admin');
                })
                ->count();

            // Get limit (custom org limit takes precedence, default to 2 for free plan)
            $recommendationsLimit = $organization->recommendations_limit ?? 2;

            return response()->json([
                'subscribed' => false,
                'plan' => new PlanResource($organization->plan),
                'started_at' => $startedAt,
                'renews_at' => $renewsAt,
                'recommendations_used' => $recommendationsUsed,
                'recommendations_limit' => $recommendationsLimit,
                'ends_at' => null,
            ]);
        }
    }
}
