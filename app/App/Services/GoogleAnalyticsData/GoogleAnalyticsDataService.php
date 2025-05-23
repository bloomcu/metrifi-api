<?php
namespace DDD\App\Services\GoogleAnalyticsData;

use Illuminate\Support\Facades\Http;
use Google\ApiCore\ApiException;
use DivisionByZeroError;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\Google\GoogleAuth;

class GoogleAnalyticsDataService
{
    private $report;

    /**
     * Run a funnel report
     * 
     * Not available in PHP SDK yet. Must use v1alpha version of the Google Analytics Data API.
     * Docs: https://developers.google.com/analytics/devguides/reporting/data/v1/funnels
     * Example: https://developers.google.com/analytics/devguides/reporting/data/v1/funnels#funnel_report_example
     * Valid dimensions and metrics: https://developers.google.com/analytics/devguides/reporting/data/v1/exploration-api-schema
     */
    public function funnelReport(Funnel $funnel, String $startDate, String $endDate, ?Array $disabledSteps = [])
    {
        $this->report = [
            'steps' => [],
            'overallConversionRate' => 0,
            'assets' => 0
        ];

        /**
         * Generate a GA funnelReport request from our app's funnel steps.
         * TODO: Refactor this using Factory and Builder patterns.
         * 
         */
        // Initialize an array to hold the structured funnel steps for the API request.
        $funnelSteps = [];

        // Iterate through each raw funnel step to structure it for the API request.
        foreach ($funnel->steps as $step) {
            $funnelFilterExpressionList = [];
            
            // If the step has no metrics, skip it.
            if ($step->metrics->isEmpty()) {
                $index = $this->getStepIndex($funnel->steps, $step->id);
                $funnel->steps->forget($index);
                continue;
            }

            // Process each metric within the step.
            foreach ($step['metrics'] as $metric) {
                // Structure the metric based on its type.
                if ($metric['metric'] === 'pageUsers') {
                    // Match exact page path
                    $funnelFilterExpressionList[] = [
                        'funnelFieldFilter' => [
                            // The page path (web) or screen class (app) on which the event was logged. 
                            // Synonymous with pagePath in GA4 reports.
                            'fieldName' => 'unifiedPagePathScreen',
                            'stringFilter' => [
                                'value' => $metric['pagePath'],
                                'matchType' => 'EXACT'
                            ]
                        ]
                    ];
                } 

                elseif ($metric['metric'] === 'pagePlusQueryStringUsers') {
                    // Match exact page path plus query string
                    $funnelFilterExpressionList[] = [
                        'funnelFieldFilter' => [
                            // The page path and query string (web) or screen class (app) on which the event was logged. 
                            // Synonymous with pagePathPlusQueryString in GA4 reports.
                            'fieldName' => 'unifiedPageScreen', 
                            'stringFilter' => [
                                'value' => $metric['pagePathPlusQueryString'],
                                'matchType' => 'EXACT',
                            ]
                        ]
                    ];
                } 

                if ($metric['metric'] === 'pageTitleUsers') {
                    // Match exact page path
                    $funnelFilterExpressionList[] = [
                        'funnelFieldFilter' => [
                            // The page title (web) or screen name (app) on which the event was logged.
                            'fieldName' => 'unifiedScreenName',
                            'stringFilter' => [
                                'value' => $metric['pageTitle'],
                                'matchType' => 'EXACT'
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

        try {
            $accessToken = $this->setupAccessToken($funnel->connection);
            $endpoint = 'https://analyticsdata.googleapis.com/v1alpha/' . $funnel->connection->uid . ':runFunnelReport?access_token=' . $accessToken;
            $gaFunnelReport = Http::post($endpoint, $funnelReportRequest)->json();

            // Bail early if no rows in report
            if (!isset($gaFunnelReport['funnelTable']['rows'])) {
                // Build report steps with no users
                foreach ($funnel->steps as $index => $step) {
                    array_push($this->report['steps'], [
                        'id' => $step['id'],
                        'name' => $step['name'],
                        'users' => 0,
                        'conversionRate' => 0,
                    ]);
                }

                // Remove disabled steps from report
                $this->removeDisabledSteps($funnel, $disabledSteps);

                // Add report to funnel
                $funnel['report'] = $this->report;

                return $funnel;
            }

            /**
             * Get users for each step
             */
            foreach ($funnel->steps as $index => $step) {
                $users = $this->getReportRowUsers($gaFunnelReport['funnelTable']['rows'], $step['name']);

                // If the step is not in the report, that means it has 0 users.
                if (!$users) {
                    array_push($this->report['steps'], [
                        'id' => $step['id'],
                        'name' => $step['name'],
                        'users' => 0,
                        'metrics' => $step['metrics']
                    ]);
                } else {
                    array_push($this->report['steps'], [
                        'id' => $step['id'],
                        'name' => $step['name'],
                        'users' => $users,
                        'metrics' => $step['metrics']
                    ]);
                }
            }

            // Calculate assets of the funnel
            $this->calculateFunnelAssets($funnel);
            
            // Remove disabled steps from report
            $this->removeDisabledSteps($funnel, $disabledSteps);

            // Calculate conversion rate of each step in report
            $this->calculateConversionRates();
            
            // Calculate the overall conversion rate.
            $this->calculateOverallConversionRate();
            
            // Add report to funnel
            $funnel['report'] = $this->report;

            return $funnel;
        } catch (ApiException $ex) {
            abort(500, 'Call failed with message: %s' . $ex->getMessage());
        }
    }
    
    /**
     * Get a list of pages and the number of users who visited them
     *
     * @param Connection $connection
     * @param [type] $startDate
     * @param [type] $endDate
     * @param string $contains
     * @return void
     */
    public function pageUsers(Connection $connection, $startDate, $endDate, $contains = '')
    {
        // Build filer expression(s)
        if ($contains) {
            $filters[] = [
                'filter' => [
                    'fieldName' => 'pagePath',
                    'stringFilter' => [
                        'matchType' => 'CONTAINS',
                        'caseSensitive' => false,
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
                ['name' => 'hostname'],
                ['name' => 'pageTitle'],
            ],
            'metrics' => [
                ['name' => 'activeUsers']
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
                        'caseSensitive' => false,
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
                ['name' => 'hostname'],
                ['name' => 'pageTitle'],
            ],
            'metrics' => [
                ['name' => 'activeUsers']
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
     * Get a list of pages by their title and the number of users who visited them
     *
     * @param Connection $connection
     * @param string $startDate
     * @param string $endDate
     * @param string $contains
     * @return array
     */
    public function pageTitleUsers(Connection $connection, $startDate, $endDate, $contains = '')
    {
        // Build filter expression(s)
        if ($contains) {
            $filters[] = [
                'filter' => [
                    'fieldName' => 'pageTitle',
                    'stringFilter' => [
                        'matchType' => 'CONTAINS',
                        'caseSensitive' => false,
                        'value' => $contains
                    ]
                ]
            ];
        } else {
            $filters = [
                [
                    'filter' => [
                        'fieldName' => 'pageTitle',
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
                ['name' => 'pageTitle'],
                ['name' => 'hostname'],
                ['name' => 'pagePath'],
            ],
            'metrics' => [
                ['name' => 'activeUsers']
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
                        'caseSensitive' => false,
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
                ['name' => 'pagePath'],
                ['name' => 'hostname'],
            ],
            'metrics' => [
                ['name' => 'activeUsers']
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
    public function formUserSubmissions(Connection $connection, $startDate, $endDate, $contains = '')
    {
        // Build filter expression(s)
        if ($contains) {
            $filters[] = [
                [
                    'filter' => [
                        'fieldName' => 'pagePath',
                        'stringFilter' => [
                            'matchType' => 'CONTAINS',
                            'caseSensitive' => false,
                            'value' => $contains
                        ]
                    ]
                ],
                [
                    'notExpression' => [ 
                        'orGroup' => [
                            'expressions' => [
                                [
                                    'filter' => [
                                        'fieldName' => 'customEvent:form_destination',
                                        'stringFilter' => [
                                            'matchType' => 'EXACT',
                                            'value' => '(not set)'
                                        ]
                                    ]
                                ],
                                [
                                    'filter' => [
                                        'fieldName' => 'customEvent:form_destination',
                                        'stringFilter' => [
                                            'matchType' => 'EXACT',
                                            'value' => ''
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        } else {
            $filters = [
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
                        'orGroup' => [
                            'expressions' => [
                                [
                                    'filter' => [
                                        'fieldName' => 'customEvent:form_destination',
                                        'stringFilter' => [
                                            'matchType' => 'EXACT',
                                            'value' => '(not set)'
                                        ]
                                    ]
                                ],
                                [
                                    'filter' => [
                                        'fieldName' => 'customEvent:form_destination',
                                        'stringFilter' => [
                                            'matchType' => 'EXACT',
                                            'value' => ''
                                        ]
                                    ]
                                ]
                            ]
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
                ['name' => 'eventName'],
                ['name' => 'pagePath'],
                ['name' => 'customEvent:form_destination'],
                ['name' => 'customEvent:form_id'],
                ['name' => 'customEvent:form_length'],
                ['name' => 'customEvent:form_submit_text'],
                ['name' => 'hostname'],
            ],
            'metrics' => [
                ['name' => 'activeUsers']
            ],
            'dimensionFilter' => [
                'andGroup' => [
                    'expressions' => $filters
                ]
            ],
            'limit' => '500',
            'metricAggregations' => ['TOTAL'],
        ]);
    }

    /**
     * Get a list of LLM sources and the number of users referred by them
     *
     * @param Connection $connection
     * @param [type] $startDate
     * @param [type] $endDate
     * @param string $contains
     * @return void
     */
    public function llmUsers(Connection $connection, $startDate, $endDate, $contains = '')
    {
        // Build filer expression(s)
        $filters[] = [
            'filter' => [
                'fieldName' => 'sessionSource',
                'stringFilter' => [
                    'matchType' => 'PARTIAL_REGEXP',
                    'caseSensitive' => false,
                    // 'value' => '(openai|copilot|chatgpt|gemini|gpt-[0-9]+|neeva|writesonic|nimble|outrider|perplexity|bard|edgeservices|astastic|copy\.ai|bnngpt)'
                    'value' => '\.openai|copilot|chatgpt|gemini|gpt-[0-9]+|neeva|writesonic|nimble|perplexity|bard|edgeservices|bnngpt|google.*gemini|google.*bard'
                ]
            ]
        ];            

        // Run the report
        return $this->runReport($connection, [
            'dateRanges' => [
                ['startDate' => $startDate, 'endDate' => $endDate]
            ],
            'dimensions' => [
                ['name' => 'sessionSource'],
            ],
            'metrics' => [
                ['name' => 'activeUsers']
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

    private function removeDisabledSteps($funnel, $disabledSteps) 
    {
        if (!$disabledSteps) {
            return;
        }

        foreach ($funnel->steps as $index => $step) {
            if (in_array($step['id'], $disabledSteps)) {

                // Find the index of the step
                $index = $this->getStepIndex($this->report['steps'], $step['id']);

                if ($index !== false && $index >= 0) {
                    array_splice($this->report['steps'], $index, 1);
                }
            }
        }
    }

    private function calculateConversionRates() 
    {
        foreach ($this->report['steps'] as $index => $step) {
            if ($index === 0) {
                $this->report['steps'][$index]['conversionRate'] = 100;
                continue;
            }
            
            try {
                $conversionRate = $step['users'] / $this->report['steps'][$index - 1]['users'];
            } catch (DivisionByZeroError $e) {
                $conversionRate = 0;
            }
            
            if ($conversionRate === 0 || is_infinite($conversionRate) || is_nan($conversionRate)) {
                $this->report['steps'][$index]['conversionRate'] = 0;
                continue;
            }

            $formatted = $conversionRate * 100; // Get a percentage

            $this->report['steps'][$index]['conversionRate'] = $formatted;
        }
    }

    private function calculateOverallConversionRate() 
    {
        $first = $this->report['steps'][0]['users'];
        $last = end($this->report['steps'])['users'];

        if ($first > 0) {
            $ocr = ($last / $first) * 100;
            $this->report['overallConversionRate'] = round($ocr, 5);
        }
    }

    private function calculateFunnelAssets($funnel) 
    {
        $lastStep = end($this->report['steps']);
        $users = $lastStep['users'];
        $assets = $users * $funnel->conversion_value;

        $this->report['assets'] = ($assets / 100);
    }

    private function getReportRowUsers($reportRows, $name) 
    {
        foreach ($reportRows as $row) {
            if (str_ends_with($row['dimensionValues'][0]['value'], $name)) {
                $users = $row['metricValues'][0]['value'];
                return $users;
            }
        }
    }

    private function getStepIndex($steps, $id) 
    {
        foreach ($steps as $index => $step) {
            if (str_contains($step['id'], $id)) {
                return $index;
            }
        }
    }
}
