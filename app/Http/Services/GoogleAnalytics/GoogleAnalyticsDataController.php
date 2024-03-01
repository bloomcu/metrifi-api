<?php
namespace DDD\Http\Services\GoogleAnalytics;

use Illuminate\Http\Request;
// use DDD\Http\Services\GoogleAnalytics\Requests\PageUsersRequest;
// use DDD\Http\Services\GoogleAnalytics\Requests\VirtualPageUsersRequest;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class GoogleAnalyticsDataController extends Controller
{
    public function pageUsers(Connection $connection, Request $request)
    {   
        $report = GoogleAnalyticsData::pageUsers(
            connection: $connection, 
            startDate: $request->startDate,
            endDate: $request->endDate,
            measurables: $request->measurables,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function pageUsersWithQueryString(Connection $connection, Request $request)
    {   
        $report = GoogleAnalyticsData::pageUsersWithQueryString(
            connection: $connection, 
            startDate: $request->startDate,
            endDate: $request->endDate,
            contains: $request->contains,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function fetchUsersByPagePath(Connection $connection, Request $request)
    {   
        $report = GoogleAnalyticsData::fetchUsersByPagePath(
            connection: $connection, 
            startDate: $request->startDate,
            endDate: $request->endDate,
            pagePaths: $request->pagePaths,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function fetchUsersByOutboundLink(Connection $connection, Request $request)
    {
        // TODO: Make a request for this
        $report = GoogleAnalyticsData::fetchUsersByOutboundLink(
            connection: $connection, 
            startDate: $request->startDate, 
            endDate: $request->endDate,
            outboundLinkUrls: $request->outboundLinkUrls,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function fetchOutboundClicksByPagePath(Connection $connection, Request $request)
    {
        $report = GoogleAnalyticsData::fetchOutboundClicksByPagePath(
            connection: $connection, 
            startDate: $request->startDate, 
            endDate: $request->endDate,
            outboundLinkUrls: $request->outboundLinkUrls,
            pagePath: $request->pagePath,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    // public function runReport(Connection $connection, Request $request)
    // {   
    //     $report = GoogleAnalyticsData::runReport($connection, $request);

    //     return response()->json([
    //         'data' => $report
    //     ], 200);
    // }

    // public function runFunnelReport(Connection $connection)
    // {   
    //     $report = GoogleAnalyticsData::runFunnelReport($connection);

    //     return response()->json([
    //         'data' => $report
    //     ], 200);
    // }
}
