<?php

namespace DDD\Http\Services\Google;

use Illuminate\Http\Request;
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

        return response()->json([
            'data' => $token
        ], 200);
    }
}
