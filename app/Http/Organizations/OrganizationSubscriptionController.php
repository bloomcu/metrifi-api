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

            return response()->json([
                'subscribed' => true,
                'plan' => new PlanResource($organization->plan),
                'started_at' => $startedAt,
                'renews_at' => $renewsAt,
                'recommendations_used' => $recommendationsUsed,
                'ends_at' => $organization->subscription('default')->ends_at,
                'upcoming_plan' => $upcomingPlan,
                'upcoming_plan_start_at' => $upcomingPlanStartAt,
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
                ->whereHas('user', function ($query) {
                    $query->where('role', '!=', 'admin');
                })
                ->count();

            return response()->json([
                'subscribed' => false,
                'plan' => new PlanResource($organization->plan),
                'started_at' => $startedAt,
                'renews_at' => $renewsAt,
                'recommendations_used' => $recommendationsUsed,
                'ends_at' => null,
            ]);
        }
    }
}
