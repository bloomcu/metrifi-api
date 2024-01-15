<?php
namespace DDD\Http\Services\GoogleAnalytics;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class GoogleAnalyticsDataController extends Controller
{
    public function runReport(Connection $connection)
    {   
        $report = GoogleAnalyticsData::runReport($connection->token, $connection->uid);

        return response()->json([
            'data' => $report
        ], 200);
    }
}
