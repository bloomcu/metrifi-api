<?php

namespace DDD\Http\Stripe;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class StripeController extends Controller
{
    public function checkout(Organization $organization, Request $request)
    {
        $session = $organization->newSubscription('default', $request->price_id)->checkout([
            // Production
            // 'success_url' => 'https://app.metrifi.com/' . $organization->slug . '/settings/billing?success=true',
            // 'cancel_url' => 'https://app.metrifi.com/' . $organization->slug . '/settings/billing?cancel=true',

            // // Staging
            'success_url' => 'https://staging.metrifi.com/' . $organization->slug . '/settings/billing?success=true',
            'cancel_url' => 'https://staging.metrifi.com/' . $organization->slug . '/settings/billing?cancel=true',

            // Local
            // 'success_url' => 'http://localhost:3000/' . $organization->slug . '/settings/billing?success=true',
            // 'cancel_url' => 'http://localhost:3000/' . $organization->slug . '/settings/billing?cancel=true',
        ]);

        return response()->json([
            'redirect_url' => $session->url
        ]);
    }

    public function billing(Organization $organization)
    {
        // Production
        // $redirect = $organization->billingPortalUrl('https://app.metrifi.com/' . $organization->slug . '/settings/billing');

        // // Staging
        $redirect = $organization->billingPortalUrl('https://staging.metrifi.com/' . $organization->slug . '/settings/billing');

        // Local
        // $redirect = $organization->billingPortalUrl('http://localhost:3000/' . $organization->slug . '/settings/billing');

        return response()->json([
            'redirect_url' => $redirect
        ]);
    }

    public function update(Organization $organization)
    {
        // Production
        // $redirect = $organization->billingPortalUrl('https://app.metrifi.com/' . $organization->slug . '/settings/billing');

        // // Staging
        $redirect = $organization->billingPortalUrl('https://staging.metrifi.com/' . $organization->slug . '/settings/billing');

        // Local
        // $redirect = $organization->billingPortalUrl('http://localhost:3000/' . $organization->slug . '/settings/billing');

        return response()->json([
            'redirect_url' => $redirect . '/subscriptions/' . $organization->subscription('default')->stripe_id . '/update',
        ]);
    }

    public function cancel(Organization $organization)
    {
        // Production
        // $redirect = $organization->billingPortalUrl('https://app.metrifi.com/' . $organization->slug . '/settings/billing');

        // Staging
        $redirect = $organization->billingPortalUrl('https://staging.metrifi.com/' . $organization->slug . '/settings/billing');

        // Local
        // $redirect = $organization->billingPortalUrl('http://localhost:3000/' . $organization->slug . '/settings/billing');

        return response()->json([
            'redirect_url' => $redirect . '/subscriptions/' . $organization->subscription('default')->stripe_id . '/cancel',
        ]);
    }
}