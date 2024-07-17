<?php
namespace DDD\Http\Services\GoogleAnalytics;

use Illuminate\Http\Request;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class GoogleAnalyticsDataController extends Controller
{
    public function funnelReport(Funnel $funnel, Request $request)
    {
        $funnel = GoogleAnalyticsData::funnelReport(
            funnel: $funnel,
            startDate: $request->startDate, 
            endDate: $request->endDate,
            disabledSteps: $request->disabledSteps,
        );

        return response()->json([
            'data' => $funnel
        ], 200);
    }

    public function pageUsers(Connection $connection, Request $request)
    {   
        $report = GoogleAnalyticsData::pageUsers(
            connection: $connection, 
            startDate: $request->startDate,
            endDate: $request->endDate,
            exact: $request->exact,
            contains: $request->contains,
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
            contains: $request->contains,
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
            contains: $request->contains,
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

    public function formUserSubmissions(Connection $connection, Request $request)
    {
        $report = GoogleAnalyticsData::formUserSubmissions(
            connection: $connection, 
            startDate: $request->startDate, 
            endDate: $request->endDate,
            contains: $request->contains,
        );

        return response()->json([
            'data' => $report
        ], 200);
    }
}
