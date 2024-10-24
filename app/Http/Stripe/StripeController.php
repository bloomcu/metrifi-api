<?php

namespace DDD\Http\Stripe;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class StripeController extends Controller
{
    // public function checkout(Organization $organization)
    // {
    //     return view('checkout');
    // }

    public function test(Organization $organization, Request $request)
    {
        Stripe::setApiKey(config('stripe.test.sk'));

        $session = Session::create([
            'line_items'  => [
                [
                    'price' => $request->price,
                    'quantity'   => 1,
                ],
            ],
            'mode' => 'subscription',
            'success_url' => 'https://staging.metrifi.com/' . $organization->slug . '/settings/billing?success=true',
            'cancel_url'  => 'https://staging.metrifi.com/' . $organization->slug . '/settings/billing?cancel=true',
        ]);

        // return redirect()->away($session->url);
        return response()->json(['redirect_url' => $session->url]);
    }

    public function live(Organization $organization)
    {
        Stripe::setApiKey(config('stripe.live.sk'));

        $session = Session::create([
            'line_items'  => [
                [
                    'price_data' => [
                        'currency'     => 'gbp',
                        'product_data' => [
                            'name' => 'T-shirt',
                        ],
                        'unit_amount'  => 500,
                    ],
                    'quantity'   => 1,
                ],
            ],
            'mode'        => 'payment',
            'success_url' => route('success'),
            'cancel_url'  => route('checkout'),
        ]);

        // return redirect()->away($session->url);
        return response()->json(['redirect_url' => $session->url]);
    }

    public function success(Organization $organization)
    {
        return response()->json(['success' => 'Stripe payment was successful']);
    }

    public function fail(Organization $organization)
    {
        return response()->json(['fail' => 'Stripe payment failed']);
    }
}