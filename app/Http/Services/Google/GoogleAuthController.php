<?php

namespace DDD\Http\Services\Google;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DDD\App\Facades\Google\GoogleAuth;
use DDD\App\Controllers\Controller;

class GoogleAuthController extends Controller
{
    /**
     * Get the Google auth URL.
     *
     * @return \Illuminate\Http\Response
     */
    public function connect(Request $request)
    {
        $url = GoogleAuth::addScope($request->scope)
            ->setState($request->state)
            ->getAuthUrl();

        Log::info('Generated Google OAuth URL', [
            'scope' => $request->scope,
            'state_length' => $request->filled('state') ? mb_strlen($request->state) : 0,
        ]);
        
        return response()->json([
            'url' => $url
        ], 200);
    }

    /**
     * Get the Google access token. 
     *
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request)
    {   
        $token = GoogleAuth::getAccessToken($request->code);

        if (is_array($token) && isset($token['error'])) {
            Log::error('Google OAuth callback returned an error', [
                'error' => $token['error'],
                'error_description' => $token['error_description'] ?? null,
            ]);
        } else {
            Log::info('Google OAuth callback retrieved token', [
                'token_keys' => is_array($token) ? array_keys($token) : null,
            ]);
        }

        return response()->json([
            'data' => $token
        ], 200);
    }
}
