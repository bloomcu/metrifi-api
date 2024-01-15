<?php
namespace DDD\Http\Services\GoogleAnalytics;

use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class GoogleAnalyticsDataController extends Controller
{
    public function runReport(Connection $connection)
    {   
        $report = GoogleAnalyticsData::runReport($connection);

        return response()->json([
            'data' => $report
        ], 200);
    }
}
