<?php

namespace DDD\Http\Services\GoogleAnalytics;

use Illuminate\Http\Request;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsAdmin;
use DDD\App\Controllers\Controller;

class GoogleAnalyticsAdminController extends Controller
{
    /**
     * List Google Analytics accounts using Google Auth token. 
     *
     * @return \Illuminate\Http\Response
     */
    public function listAccounts(Request $request)
    {
        $accounts = GoogleAnalyticsAdmin::listAccounts($request->token);
        
        return response()->json([
            'data' => $accounts
        ], 200);
    }

    // /**
    //  * List users' Google Analytics accounts. 
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function listAccounts()
    // {
    //     $accounts = GoogleAnalyticsAdmin::listUserAccounts(auth()->user());
        
    //     return response()->json([
    //         'accounts' => $accounts
    //     ], 200);
    // }
}
