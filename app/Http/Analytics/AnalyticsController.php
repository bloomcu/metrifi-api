<?php
// TODO: Maybe rename this to ReportController and make it a single action controller

namespace DDD\Http\Analytics;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Integrations\Integration;
use DDD\App\Facades\Google\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class AnalyticsController extends Controller
{
    public function runReport(Organization $organization, Integration $integration)
    {   
        $report = GoogleAnalyticsData::runReport($integration->token, $integration->uid);

        return response()->json([
            'data' => $report
        ], 200);
    }
}
