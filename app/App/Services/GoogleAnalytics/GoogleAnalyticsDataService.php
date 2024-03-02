<?php
namespace DDD\App\Services\GoogleAnalytics;

use Illuminate\Support\Facades\Http;
use Google\ApiCore\ApiException;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\Google\GoogleAuth;

class GoogleAnalyticsDataService
{
    public function pageUsers(Connection $connection, $startDate, $endDate, $measurables = [])
    {
        // Default filter expression
        // $filters = [
        //     [
        //         'filter' => [
        //             'fieldName' => 'pagePath',
        //             'stringFilter' => [
        //                 'matchType' => 'BEGINS_WITH',
        //                 'value' => '/' // Cannot be empty
        //             ]
        //         ]
        //     ]
        // ];
        // $filters = [];

        // If page path is specified, filter on it
        if ($measurables && count($measurables)) {
            foreach ($measurables as $measurable) {
                $filters[] = [
                    'filter' => [
                        'fieldName' => 'pagePath',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'caseSensitive' => true,
                            'value' => $measurable
                        ]
                    ]
                ];
            }
        } else {
            $filters = [
                [
                    'filter' => [
                        'fieldName' => 'pagePath',
                        'stringFilter' => [
                            'matchType' => 'BEGINS_WITH',
                            'value' => '/'
                        ]
                    ]
                ]
            ];
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
                ['name' => 'totalUsers']
            ],
            'dimensionFilter' => [
                'orGroup' => [
                    'expressions' => $filters
                ]
            ],
            'limit' => '250',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function pagePlusQueryStringUsers(Connection $connection, $startDate, $endDate, $measurables = [])
    {
        // Default filter expression
        // $filters = [
        //     [
        //         'filter' => [
        //             'fieldName' => 'pagePathPlusQueryString',
        //             'stringFilter' => [
        //                 'matchType' => 'FULL_REGEXP',
        //                 'value' => '.+' // Cannot be empty
        //             ]
        //         ]
        //     ]
        // ];
        // $filters = [];

        // If contains array are specified, filter on them
        if ($measurables && count($measurables)) {
            foreach($measurables as $measurable) {
                $filters[] = [
                    'filter' => [
                        'fieldName' => 'pagePathPlusQueryString',
                        'stringFilter' => [
                            'matchType' => 'CONTAINS',
                            'caseSensitive' => true,
                            'value' => $measurable
                        ]
                    ]
                ];
            }
        } else {
            $filters = [
                [
                    'filter' => [
                        'fieldName' => 'pagePathPlusQueryString',
                        'stringFilter' => [
                            'matchType' => 'FULL_REGEXP',
                            'value' => '.+' // Result cannot be empty
                        ]
                    ]
                ]
            ];
        }

        // Run the report
        return $this->runReport($connection, [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate]
            ],
            'dimensions' => [
                ['name' => 'pagePathPlusQueryString'],
            ],
            'metrics' => [
                ['name' => 'totalUsers']
            ],
            'dimensionFilter' => [
                'orGroup' => [
                    'expressions' => $filters
                ]
            ],
            'limit' => '250',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function outboundLinkUsers(Connection $connection, $startDate, $endDate, $outboundLinkUrls = null)
    {
        // By default, return all outbound link clicks
        $expressions = [
            'filter' => [
                'fieldName' => 'linkUrl',
                'stringFilter' => [
                    'matchType' => 'FULL_REGEXP',
                    'value' => '.+' // Cannot be empty
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
                // ['name' => 'linkDomain'],
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'totalUsers']
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

    public function outboundLinkByPagePathUsers(Connection $connection, $startDate, $endDate, $outboundLinkUrls = null, $pagePath)
    {
        $fullReport = $this->outboundLinkUsers($connection, $startDate, $endDate, $outboundLinkUrls);

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
            
            if (count($dimensionValues) == 2) {
                // The third item in "dimensionValues" represents the page path
                if (isset($dimensionValues[1]['value']) && $dimensionValues[1]['value'] === $pagePath) {
                    // The metric value represents the event count
                    $eventCount = isset($metricValues[0]['value']) ? $metricValues[0]['value'] : 0;

                    // The first item in "dimensionValues" represents the link URL
                    array_push($report['links'], [
                        'linkUrl' => isset($dimensionValues[0]['value']) ? $dimensionValues[0]['value'] : '',
                        'clicks' => $eventCount,
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
}
