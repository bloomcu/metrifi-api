<?php
// TODO: Maybe move this to the Google Analytics service folder

namespace DDD\App\Services\GoogleAnalytics;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\ApiException;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\Google\GoogleAuth;

class GoogleAnalyticsDataService
{
    public function fetchPageViews(Connection $connection, $startDate, $endDate)
    {
        return $this->runReport($connection, [
            'dateRanges' => [
                [ 
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ]
            ],
            'dimensions' => [
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'screenPageViews']
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'pagePath',
                    'stringFilter' => [
                        'matchType' => 'BEGINS_WITH',
                        'value' => '/'
                    ]
                ]
            ],
            'limit' => '250'
        ]);
    }

    public function fetchOutboundClicks(Connection $connection, $startDate, $endDate)
    {
        return $this->runReport($connection, [
            'dateRanges' => [
                [ 
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ]
            ],
            'dimensions' => [
                ['name' => 'linkUrl'],
                ['name' => 'linkDomain'],
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'eventCount']
            ],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'linkUrl',
                    'stringFilter' => [
                        'matchType' => 'FULL_REGEXP',
                        'value' => '.+'
                    ]
                ]
            ],
            'limit' => '250'
        ]);
    }

    /**
     * Run a report
     * 
     * Docs: https://cloud.google.com/php/docs/reference/analytics-data/latest/Google.Analytics.Data.V1beta.BetaAnalyticsDataClient#_runReport
     * PHP Client: https://github.com/googleapis/php-analytics-data/blob/master/samples/V1beta/BetaAnalyticsDataClient/run_report.php
     */
    public function runReport(Connection $connection, $params)
    {
        try {
            $accessToken = $this->setupAccessToken($connection);
            
            $endpoint = 'https://analyticsdata.googleapis.com/v1beta/' . $connection->uid . ':runReport?access_token=' . $accessToken;

            $response = Http::post($endpoint, $params)->json();

            return $response;
        } catch (ApiException $ex) {
            abort(500, 'Call failed with message: %s' . $ex->getMessage());
        }
    }

    /**
     * Run a funnel report
     * 
     * Not available in PHP SDK yet. Must use v1alpha version of the Google Analytics Data API.
     * Docs: https://developers.google.com/analytics/devguides/reporting/data/v1/funnels
     * Example: https://developers.google.com/analytics/devguides/reporting/data/v1/funnels#funnel_report_example
     * Valid dimensions and metrics: https://developers.google.com/analytics/devguides/reporting/data/v1/exploration-api-schema
     */
    // public function runFunnelReport(Connection $connection)
    // {
    //     $accessToken = $this->setupAccessToken($connection);

    //     try {
    //         $response = Http::post('https://analyticsdata.googleapis.com/v1alpha/' . $connection->uid . ':runFunnelReport?access_token=' . $accessToken, 
    //         [
    //             'dateRanges' => [
    //                 'startDate' => '7daysAgo',
    //                 'endDate' => 'today'
    //             ],
    //             'funnel' => [
    //                 'isOpenFunnel' => false,
    //                 'steps' => [
    //                     [
    //                         'name' => 'Homepage',
    //                         'filterExpression' => [
    //                             'funnelFieldFilter' => [
    //                                 'fieldName' => 'pageLocation',
    //                                 'stringFilter' => [
    //                                     'value' => 'https://www.lbsfcu.org/',
    //                                     'matchType' => 'EXACT'
    //                                 ]
    //                             ]
    //                         ]
    //                     ],
    //                     [
    //                         'name' => 'Auto Loan',
    //                         'filterExpression' => [
    //                             'funnelFieldFilter' => [
    //                                 'fieldName' => 'pageLocation',
    //                                 'stringFilter' => [
    //                                     'value' => 'https://www.lbsfcu.org/loans/auto/auto-loans/',
    //                                     'matchType' => 'EXACT'
    //                                 ]
    //                             ]
    //                         ]
    //                     ]
    //                 ]
    //             ]
    //         ])->json();

    //         return $response;
    //     } catch (ApiException $ex) {
    //         abort(500, 'Call failed with message: %s' . $ex->getMessage());
    //     }
    // }

    /**
     * Setup credentials for Analytics Data Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    // TODO: Should this be a constructor, or a standalone class or helper?
    private function setupAccessToken(Connection $connection)
    {
        $validConnection = GoogleAuth::validateConnection($connection);
 
        return $validConnection->token['access_token']; // TODO: consider renaming 'token' to 'credentials'
     }

    /**
     * Setup credentials for Analytics Data Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
     // TODO: We only need this method when using the PHP SDK. When using the REST API, we can just use the access token directly.
     // TODO: Should this be a constructor, or a standalone class or helper?
    // private function setupCredentials(Connection $connection)
    // {
    //     $validConnection = GoogleAuth::validateConnection($connection);

    //     $credentials = CredentialsWrapper::build([
    //         'keyFile' => [
    //             'type'          => 'authorized_user',
    //             'client_id'     => config('services.google.client_id'),
    //             'client_secret' => config('services.google.client_secret'),
    //             'refresh_token' => $validConnection->token['access_token'], // TODO: consider renaming 'token' to 'credentials'
    //         ],
    //         'scopes'  => [
    //             'https://www.googleapis.com/auth/analytics',
    //             'https://www.googleapis.com/auth/analytics.readonly',
    //         ]
    //     ]);

    //     return $credentials;
    // }
}
