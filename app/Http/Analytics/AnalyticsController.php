<?php
// TODO: Maybe rename this to ReportController and make it a single action controller

namespace DDD\Http\Analytics;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\Google\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function runReport(Organization $organization, Connection $connection)
    {   
        $report = GoogleAnalyticsData::runReport($connection->token, $connection->uid);

        return response()->json([
            'data' => $report
        ], 200);
    }
}
