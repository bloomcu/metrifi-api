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
            'success_url' => 'https://staging.metrifi.com/' . $organization->slug . '/settings/billing?success=true',
            'cancel_url' => 'https://staging.metrifi.com/' . $organization->slug . '/settings/billing?cancel=true',
        ]);

        return response()->json(['redirect_url' => $session->url]);
    }
}