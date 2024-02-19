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
    public function fetchPageViews(Connection $connection, $startDate, $endDate, $pagePaths = null)
    {
        // By default, return all pages where path begins with '/'
        $expressions = [
            'filter' => [
                'fieldName' => 'pagePath',
                'stringFilter' => [
                    'matchType' => 'BEGINS_WITH',
                    'value' => '/'
                ]
            ]
        ];

        // If specific page paths are being requested, filter on them
        if ($pagePaths) {
            $expressions = collect($pagePaths)->map(fn ($path) => [
                'filter' => [
                    'fieldName' => 'pagePath',
                    'stringFilter' => [
                        'matchType' => 'EXACT',
                        'value' => $path 
                    ]
                ]
            ])->toArray();
        }

        // Run the report
        return $this->runReport($connection, [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate]
            ],
            'dimensions' => [
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'screenPageViews']
            ],
            'dimensionFilter' => [
                'orGroup' => [
                    'expressions' => [
                        ...$expressions
                    ]
                ]
            ],
            'limit' => '250',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function fetchOutboundClicks(Connection $connection, $startDate, $endDate, $outboundLinkUrls = null)
    {
        // By default, return all outbound link clicks
        $expressions = [
            'filter' => [
                'fieldName' => 'linkUrl',
                'stringFilter' => [
                    'matchType' => 'FULL_REGEXP',
                    'value' => '.+' // Match any page path
                ]
            ]
        ];

        // If outbound link urls are specified, filter on them
        if ($outboundLinkUrls) {
            $expressions = collect($outboundLinkUrls)->map(fn ($linkUrl) => [
                'filter' => [
                    'fieldName' => 'linkUrl',
                    'stringFilter' => [
                        'matchType' => 'EXACT',
                        'value' => $linkUrl
                    ]
                ]
            ])->toArray();
        }
        
        return $this->runReport($connection, [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate]
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
                'orGroup' => [
                    'expressions' => [
                        ...$expressions
                    ]
                ]
            ],
            'limit' => '250',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function fetchOutboundClicksByPagePath(Connection $connection, $startDate, $endDate, $outboundLinkUrls = null, $pagePath)
    {
        $fullReport = $this->fetchOutboundClicks($connection, $startDate, $endDate, $outboundLinkUrls);
        
        $report = [
            'links' => [],
            'total' => 0
        ];

        if (!isset($fullReport['rows'])) {
            return $report;
        }

        foreach ($fullReport['rows'] as $row) {
            // Dimension values include the link URL, link domain, and page path for each row.
            $dimensionValues = isset($row['dimensionValues']) ? $row['dimensionValues'] : [];

            // Metric value represents the event count
            $metricValues = isset($row['metricValues']) ? $row['metricValues'] : [];
            
            // The third item in "dimensionValues" represents the page path
            if (count($dimensionValues) == 3) {
                if (isset($dimensionValues[2]['value']) && $dimensionValues[2]['value'] === $pagePath) {
                    // The metric value represents the event count
                    $eventCount = isset($metricValues[0]['value']) ? $metricValues[0]['value'] : 0;
    
                    // The first item in "dimensionValues" represents the link URL
                    array_push($report['links'], [
                        'linkUrl' => $dimensionValues[0]['value'],
                        'linkDomain' => $dimensionValues[1]['value'],
                        'clicks' => $eventCount
                    ]);
    
                    // Add the event count to the total
                    $report['total'] += $eventCount;
                }
            }
        }

        return $report;
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
