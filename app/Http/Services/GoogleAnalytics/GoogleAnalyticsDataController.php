<?php
namespace DDD\Http\Services\GoogleAnalytics;

use Illuminate\Http\Request;
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
            pagePaths: $request->pagePaths,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function pagePlusQueryStringUsers(Connection $connection, Request $request)
    {   
        $report = GoogleAnalyticsData::pagePlusQueryStringUsers(
            connection: $connection, 
            startDate: $request->startDate,
            endDate: $request->endDate,
            pagePathPlusQueryStrings: $request->pagePathPlusQueryStrings,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function outboundLinkUsers(Connection $connection, Request $request)
    {
        $report = GoogleAnalyticsData::outboundLinkUsers(
            connection: $connection, 
            startDate: $request->startDate, 
            endDate: $request->endDate,
            linkUrls: $request->linkUrls,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }

    public function outboundLinkByPagePathUsers(Connection $connection, Request $request)
    {
        $report = GoogleAnalyticsData::outboundLinkByPagePathUsers(
            connection: $connection, 
            startDate: $request->startDate, 
            endDate: $request->endDate,
            sourcePagePath: $request->sourcePagePath,
            linkUrls: $request->linkUrls,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }
}
