<?php
namespace DDD\App\Services\GoogleAnalyticsData;

use Illuminate\Support\Facades\Http;
use Google\ApiCore\ApiException;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\Google\GoogleAuth;

class GoogleAnalyticsDataService
{
    /**
     * Run a funnel report
     * 
     * Not available in PHP SDK yet. Must use v1alpha version of the Google Analytics Data API.
     * Docs: https://developers.google.com/analytics/devguides/reporting/data/v1/funnels
     * Example: https://developers.google.com/analytics/devguides/reporting/data/v1/funnels#funnel_report_example
     * Valid dimensions and metrics: https://developers.google.com/analytics/devguides/reporting/data/v1/exploration-api-schema
     */
    public function funnelReport(Connection $connection, String $startDate, String $endDate, Array $steps)
    {
        $accessToken = $this->setupAccessToken($connection);
        $endpoint = 'https://analyticsdata.googleapis.com/v1alpha/' . $connection->uid . ':runFunnelReport?access_token=' . $accessToken;
        
        /**
         * Generate a GA funnelReport request from our app's funnel steps
         * TODO: Refactor this using Factory and Builder patterns
         * 
         */

        // Initialize an array to hold the structured funnel steps for the API request.
        $funnelSteps = [];

        // Iterate through each raw funnel step to structure it for the API request.
        foreach ($steps as $step) {
            $funnelFilterExpressionList = [];

            // Process each metric within the step.
            foreach ($step['metrics'] as $metric) {
                // Structure the metric based on its type.
                if ($metric['metric'] === 'pageUsers') {
                    $funnelFilterExpressionList[] = [
                        'funnelFieldFilter' => [
                            'fieldName' => 'unifiedPagePathScreen',
                            'stringFilter' => [
                                'value' => $metric['pagePath'],
                                'matchType' => 'EXACT'
                            ]
                        ]
                    ];
                } elseif ($metric['metric'] === 'formSubmissions') {
                    $funnelFilterExpressionList[] = [
                        'funnelEventFilter' => [
                            'eventName' => 'onsite_form_submission',
                            'funnelParameterFilterExpression' => [
                                'funnelParameterFilter' => [
                                    'eventParameterName' => 'page_location',
                                    'stringFilter' => [
                                        'matchType' => 'EXACT',
                                        'value' => $metric['pageLocation']
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
            }

            // Add the structured step to the funnel steps array.
            $funnelSteps[] = [
                'name' => $step['name'],
                'filterExpression' => [
                    'orGroup' => [
                        'expressions' => $funnelFilterExpressionList
                    ]
                ]
            ];
        }

        // Prepare the full funnel structure for the API request.
        $funnelReportRequest = [
            'dateRanges' => [
                [
                    'startDate' => $startDate,
                    'endDate' => $endDate
                ]
            ],
            'funnel' => [
                'isOpenFunnel' => false,
                'steps' => $funnelSteps
            ]
        ];

        try {
            $gaFunnelReport = Http::post($endpoint, $funnelReportRequest)->json();
            
            /**
             * Format the funnel report
             * TODO: Refactor this using a design pattern such as Strategy or Factory
             * 
             */

            // Initialize the report array
            $report = [
                'steps' => [],
                'overallConversionRate' => ''
            ];

            // Variables to store first and last step users for overall conversion calculation
            $firstStepUsers = 0;
            $lastStepUsers = 0;

            // Store the previous step's completion rate
            $previousRate = 0;

            // Iterate through each step in the funnel
            foreach ($gaFunnelReport['funnelTable']['rows'] as $index => $row) {
                $name = $row['dimensionValues'][0]['value'];
                $users = $row['metricValues'][0]['value'];
                $conversionRate = $previousRate > 0 ? number_format($previousRate * 100, 2) . '%' : '';
                
                // Add the step information to the report
                $report['steps'][] = [
                    'name' => $name,
                    'users' => $users,
                    'conversionRate' => $conversionRate,
                ];

                // Set first step users
                if ($index === 0) {
                    $firstStepUsers = $row['metricValues'][0]['value'];
                }

                // Update last step users with every iteration
                $lastStepUsers = $users;

                // Update the previous rate for the next iteration
                $previousRate = $row['metricValues'][1]['value'];
            }

            // Calculate the overall conversion rate
            if ($firstStepUsers > 0) {
                $overallConversionRate = ($lastStepUsers / $firstStepUsers) * 100;
                $report['overallConversionRate'] = number_format($overallConversionRate, 2) . '%';
            }

            return $report;

            // /**
            //  * Format the funnel report
            //  * TODO: Refactor this using a design pattern such as Strategy or Factory
            //  * 
            //  */

            // // Initialize the report array
            // $report = [
            //     'steps' => [],
            //     'overallConversionRate' => ''
            // ];

            // // Variables to store first and last step users for overall conversion calculation
            // $firstStepUsers = 0;
            // $lastStepUsers = 0;

            // // Iterate through each step in the funnel
            // foreach ($gaFunnelReport['funnelTable']['rows'] as $index => $row) {
            //     $stepName = $row['dimensionValues'][0]['value'];
            //     $users = (int)$row['metricValues'][0]['value']; // Cast users to int for accurate formatting
            //     $conversionRate = ''; // Initialize as empty string

            //     // Check if there is a conversion rate value and format it as a percentage
            //     if (isset($row['metricValues'][1]['value'])) {
            //         $conversionRateValue = floatval($row['metricValues'][1]['value']) * 100;
            //         $conversionRate = number_format($conversionRateValue, 2) . '%';
            //     }

            //     // Add the step information to the report
            //     $report['steps'][] = [
            //         'name' => $stepName,
            //         'users' => $users,
            //         'conversionRate' => $conversionRate,
            //     ];

            //     // Set first step users
            //     if ($index === 0) {
            //         $firstStepUsers = (int)$users;
            //     }

            //     // Update last step users with every iteration
            //     $lastStepUsers = (int)$users;
            // }

            // // Calculate the overall conversion rate
            // if ($firstStepUsers > 0) {
            //     $overallConversionRate = ($lastStepUsers / $firstStepUsers) * 100;
            //     $report['overallConversionRate'] = number_format($overallConversionRate, 2) . '%';
            // }

            // return $report;
        } catch (ApiException $ex) {
            abort(500, 'Call failed with message: %s' . $ex->getMessage());
        }
    }
    
    public function pageUsers(Connection $connection, $startDate, $endDate, $pagePaths = [])
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
        if ($pagePaths && count($pagePaths)) {
            foreach ($pagePaths as $pagePath) {
                $filters[] = [
                    'filter' => [
                        'fieldName' => 'pagePath',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'caseSensitive' => true,
                            'value' => $pagePath
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
            'limit' => '500',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function pagePlusQueryStringUsers(Connection $connection, $startDate, $endDate, $pagePathPlusQueryStrings = [])
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
        if ($pagePathPlusQueryStrings && count($pagePathPlusQueryStrings)) {
            foreach($pagePathPlusQueryStrings as $pagePathPlusQueryString) {
                $filters[] = [
                    'filter' => [
                        'fieldName' => 'pagePathPlusQueryString',
                        'stringFilter' => [
                            'matchType' => 'CONTAINS',
                            'caseSensitive' => true,
                            'value' => $pagePathPlusQueryString
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
            'limit' => '500',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function outboundLinkUsers(Connection $connection, $startDate, $endDate, $linkUrls = null)
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
        if ($linkUrls) {
            $expressions = collect($linkUrls)->map(fn ($linkUrl) => [
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
            'limit' => '500',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    public function outboundLinkByPagePathUsers(Connection $connection, $startDate, $endDate, $linkUrls = null, $sourcePagePath)
    {
        $fullReport = $this->outboundLinkUsers($connection, $startDate, $endDate, $linkUrls);

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
                if (isset($dimensionValues[1]['value']) && $dimensionValues[1]['value'] === $sourcePagePath) {
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
}
