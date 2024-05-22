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
         * Generate a GA funnelReport request from our app's funnel steps.
         * TODO: Refactor this using Factory and Builder patterns.
         * 
         */

        // Initialize an array to hold the structured funnel steps for the API request.
        $funnelSteps = [];
        
        // Iterate through each raw funnel step to structure it for the API request.
        foreach ($steps as $step) {
            $funnelFilterExpressionList = [];

            // If the step has no metrics, skip it.
            if (!$step['metrics']) {
                $index = $this->getStepIndex($steps, $step['id']);
                array_splice($steps, $index, 1);
                continue;
            }

            // Process each metric within the step.
            foreach ($step['metrics'] as $metric) {
                // Structure the metric based on its type.
                if ($metric['metric'] === 'pageUsers') {
                    $funnelFilterExpressionList[] = [
                        'funnelFieldFilter' => [
                            'fieldName' => 'unifiedPagePathScreen', // Synonymous with pagePath in GA4 reports
                            'stringFilter' => [
                                'value' => $metric['pagePath'],
                                'matchType' => 'EXACT'
                            ]
                        ]
                    ];
                } 
                elseif ($metric['metric'] === 'pagePlusQueryStringUsers') {
                    $funnelFilterExpressionList[] = [
                        'funnelFieldFilter' => [
                            'fieldName' => 'unifiedPageScreen', // Synonymous with pagePathPlusQueryString in GA4 reports
                            'stringFilter' => [
                                'value' => $metric['pagePathPlusQueryString'],
                                'matchType' => 'EXACT',
                            ]
                        ]
                    ];
                } 
                elseif ($metric['metric'] === 'outboundLinkUsers') {
                    $funnelFilterExpressionList[] = [
                        'andGroup' => [
                            'expressions' => [
                                [
                                    'funnelFieldFilter' => [
                                        'fieldName' => 'linkUrl',
                                        'stringFilter' => [
                                            'value' => $metric['linkUrl'],
                                            'matchType' => 'EXACT',
                                        ]
                                    ]
                                ],
                                [
                                    'funnelFieldFilter' => [
                                        'fieldName' => 'unifiedPagePathScreen', // Synonymous with pagePath in GA4 reports
                                        'stringFilter' => [
                                            'value' => $metric['pagePath'],
                                            'matchType' => 'EXACT',
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                } 
                elseif ($metric['metric'] === 'formUserSubmissions') {
                    $funnelFilterExpressionList[] = [
                        'andGroup' => [
                            'expressions' => [
                                [
                                    'funnelFieldFilter' => [
                                        'fieldName' => 'eventName',
                                        'stringFilter' => [
                                            'value' => 'form_submit',
                                            'matchType' => 'EXACT',
                                        ]
                                    ]
                                ],
                                [
                                    'funnelFieldFilter' => [
                                        'fieldName' => 'unifiedPagePathScreen', // Synonymous with pagePath in GA4 reports
                                        'stringFilter' => [
                                            'value' => $metric['pagePath'],
                                            'matchType' => 'EXACT',
                                        ]
                                    ]
                                ],
                                [
                                    'funnelEventFilter' => [
                                        'eventName' => 'form_submit',
                                        'funnelParameterFilterExpression' => [
                                            'funnelParameterFilter' => [
                                                'eventParameterName' => 'form_destination',
                                                'stringFilter' => [
                                                    'matchType' => 'EXACT',
                                                    'value' => $metric['formDestination']
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'funnelEventFilter' => [
                                        'eventName' => 'form_submit',
                                        'funnelParameterFilterExpression' => [
                                            'funnelParameterFilter' => [
                                                'eventParameterName' => 'form_id',
                                                'stringFilter' => [
                                                    'matchType' => 'EXACT',
                                                    'value' => $metric['formId']
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'funnelEventFilter' => [
                                        'eventName' => 'form_submit',
                                        'funnelParameterFilterExpression' => [
                                            'funnelParameterFilter' => [
                                                'eventParameterName' => 'form_length',
                                                'stringFilter' => [
                                                    'matchType' => 'EXACT',
                                                    'value' => $metric['formLength']
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    'funnelEventFilter' => [
                                        'eventName' => 'form_submit',
                                        'funnelParameterFilterExpression' => [
                                            'funnelParameterFilter' => [
                                                'eventParameterName' => 'form_submit_text',
                                                'stringFilter' => [
                                                    'matchType' => 'EXACT',
                                                    'value' => $metric['formSubmitText']
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ]
                    ];
                }
            }

            // return $funnelFilterExpressionList;

            // Add the structured step to the funnel report API request as a filter expression.
            $funnelSteps[] = [
                'name' => $step['name'],
                'filterExpression' => [
                    'orGroup' => [
                        'expressions' => $funnelFilterExpressionList
                    ]
                ]
            ];
        }

        // return $funnelSteps;

        // Prepare the full structure for the funnel report API request.
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
        
        // return $funnelReportRequest;

        try {
            $gaFunnelReport = Http::post($endpoint, $funnelReportRequest)->json();
            // return $gaFunnelReport;
            
            /**
             * Format the funnel report.
             * TODO: Refactor this using a design pattern such as Strategy or Factory.
             * 
             */

            // Initialize the report array.
            // $report = [
            //     'steps' => [],
            //     'overallConversionRate' => '0%'
            // ];

            // Variables to store first and last step users for overall conversion calculation.
            // $firstStepUsers = 0;
            // $lastStepUsers = 0;

            // Store the previous step's completion rate.
            // $previousRate = 0;
            
            // Iterate through each step in the funnel.
            // TODO: Check if we have any rows, if not, zero out all the original steps.
            foreach ($steps as $index => $step) {
            
                // If the step is not in the report, that means it has 0 users.
                // Add it with 0 users and 0% conversion rate.
                // if (!isset($gaFunnelReport['funnelTable']['rows'][$index])) {
                //     // $steps[$index]['invalid'] = true;
                //     $steps[$index]['users'] = '0';
                //     continue;
                // };
                
                $users = $this->getReportRowUsers($gaFunnelReport['funnelTable']['rows'], $step['name']);

                if ($users) {
                    $steps[$index]['users'] = $users;
                } else {
                    $steps[$index]['users'] = '0';
                }
            }

            // Iterate through each step in the funnel.
            // TODO: Check if we have any rows, if not, zero out all the original steps.
            // foreach ($steps as $index => $step) {

            //     // Find the step in the report
            //     foreach ($gaFunnelReport['funnelTable']['rows'] as $key => $row) {
            //         if (str_contains($row['dimensionValues'][0]['value'], $step['name'])) {
            //             // Get users count
            //             $users = $row['metricValues'][0]['value'];
    
            //             // Add users count to step
            //             $steps[$index]['users'] = $users;

            //             continue;
            //         }
            //     }
            // }

            // Calculate the overall conversion rate.
            // $firstStepUsers = $report['steps'][0]['users'];
            // $lastStepUsers = end($report['steps'])['users'];

            // if ($firstStepUsers > 0) {
            //     $overallConversionRate = ($lastStepUsers / $firstStepUsers) * 100;
            //     $report['overallConversionRate'] = number_format($overallConversionRate, 2) . '%';
            // }

            return $steps;

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

    private function getReportRowUsers($reportRows, $name) {
        foreach ($reportRows as $row) {
            if (str_ends_with($row['dimensionValues'][0]['value'], $name)) {
                $users = $row['metricValues'][0]['value'];
                return $users;
            }
        }
    }

    private function getStepIndex($steps, $id) {
        foreach ($steps as $index => $step) {
            if (str_contains($step['id'], $id)) {
                return $index;
            }
        }
    }
    
    /**
     * Get a list of pages and the number of users who visited them
     *
     * @param Connection $connection
     * @param [type] $startDate
     * @param [type] $endDate
     * @param array $exact
     * @param string $contains
     * @return void
     */
    public function pageUsers(Connection $connection, $startDate, $endDate, $exact = [], $contains = '')
    {
        // Build filer expression(s)
        if ($exact && count($exact)) {
            foreach ($exact as $pagePath) {
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
        } elseif ($contains) {
            $filters[] = [
                'filter' => [
                    'fieldName' => 'pagePath',
                    'stringFilter' => [
                        'matchType' => 'CONTAINS',
                        'caseSensitive' => true,
                        'value' => $contains
                    ]
                ]
            ];
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

    /**
     * Get a list of pages with query strings and the number of users who visited them
     *
     * @param Connection $connection
     * @param [type] $startDate
     * @param [type] $endDate
     * @param array $pagePathPlusQueryStrings
     * @return void
     */
    public function pagePlusQueryStringUsers(Connection $connection, $startDate, $endDate, $contains = '')
    {
        // Build filer expression(s)
        if ($contains) {
            $filters[] = [
                'filter' => [
                    'fieldName' => 'pagePathPlusQueryString',
                    'stringFilter' => [
                        'matchType' => 'CONTAINS',
                        // 'caseSensitive' => true,
                        'value' => $contains
                    ]
                ]
            ];
        } else {
            $filters = [
                [
                    'filter' => [
                        'fieldName' => 'pagePathPlusQueryString',
                        'stringFilter' => [
                            'matchType' => 'FULL_REGEXP',
                            'value' => '.+' // Cannot be empty
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

    /**
     * Get a list of pages with outbound link clicks
     *
     * @param Connection $connection
     * @param [type] $startDate
     * @param [type] $endDate
     * @param [type] $linkUrls
     * @return void
     */
    public function outboundLinkUsers(Connection $connection, $startDate, $endDate, $contains = '')
    {
        // Build filer expression(s)
        if ($contains) {
            $filters[] = [
                'filter' => [
                    'fieldName' => 'linkUrl',
                    'stringFilter' => [
                        'matchType' => 'CONTAINS',
                        'value' => $contains
                    ]
                ]
            ];
        } else {
            $filters = [
                [
                    'filter' => [
                        'fieldName' => 'linkUrl',
                        'stringFilter' => [
                            'matchType' => 'FULL_REGEXP',
                            'value' => '.+' // Cannot be empty
                        ]
                    ]
                ]
            ];
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
                    'expressions' => $filters
                ]
            ],
            'limit' => '500',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    /**
     * Get number of users who clicked on an outbound link from a specific page
     *
     * @param Connection $connection
     * @param [string] $startDate
     * @param [string] $endDate
     * @param [type] $linkUrls
     * @param [type] $sourcePagePath
     * @return void
     */
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
     * Get a list of all pages with user form submissions by form id
     *
     * Enhanced measurement events and parameters: https://support.google.com/analytics/answer/9216061?hl=en&ref_topic=13367566&sjid=3386798091051746172-NC
     * Tracking form submissions: https://ezsegment.com/automatic-form-interaction-tracking-in-ga4/
     * 
     * 
     * @param Connection $connection
     * @param [string] $startDate
     * @param [string] $endDate
     * @return void
     */
    public function formUserSubmissions(Connection $connection, $startDate, $endDate)
    {
        return $this->runReport($connection, [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate]
            ],
            'dimensions' => [
                ['name' => 'eventName'],
                ['name' => 'pagePath'],
                ['name' => 'customEvent:form_destination'],
                ['name' => 'customEvent:form_id'],
                ['name' => 'customEvent:form_length'],
                ['name' => 'customEvent:form_submit_text'],
            ],
            'metrics' => [
                ['name' => 'totalUsers']
            ],
            // 'dimensionFilter' => [
            //     'filter' => [
            //         'fieldName' => 'eventName',
            //         'stringFilter' => [
            //             'matchType' => 'EXACT',
            //             'value' => 'form_submit'
            //         ]
            //     ]
            // ],
            'dimensionFilter' => [
                'andGroup' => [
                    'expressions' => [
                        [
                            'filter' => [
                                'fieldName' => 'eventName',
                                'stringFilter' => [
                                    'matchType' => 'EXACT',
                                    'value' => 'form_submit'
                                ]
                            ]
                        ],
                        [
                            'notExpression' => [ 
                                'filter' => [
                                    'fieldName' => 'customEvent:form_destination',
                                    'stringFilter' => [
                                        'matchType' => 'EXACT',
                                        'value' => '(not set)' // Cannot contain "(not set)"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'limit' => '500',
            'metricAggregations' => ['TOTAL'],
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
