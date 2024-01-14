<?php
// TODO: Maybe move this to the Google Analytics service folder

namespace DDD\App\Services\Google;

use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\ApiException;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\DimensionOrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;

class GoogleAnalyticsDataService
{
    /**
     * Run a report
     * 
     * Docs: https://cloud.google.com/php/docs/reference/analytics-data/latest/Google.Analytics.Data.V1beta.BetaAnalyticsDataClient#_runReport
     * PHP Client: https://github.com/googleapis/php-analytics-data/blob/master/samples/V1beta/BetaAnalyticsDataClient/run_report.php
     */
    public function runReport($token, $property)
    {
        $client = new BetaAnalyticsDataClient(['credentials' => $this->setupCredentials($token)]);

        // Prepare the request message.
        // $request = new RunReportRequest();
        $request = (new RunReportRequest())
	    ->setProperty($property)
	    ->setDateRanges([
	        new DateRange([
	            'start_date' => '2023-12-14',
	            'end_date' => 'today',
	        ]),
	    ])
	    ->setDimensions([new Dimension([
	            'name' => 'date',
	        ]),
	    ])
	    ->setMetrics([new Metric([
	            'name' => 'activeUsers',
	        ])
	    ])
	    ->setOrderbys([new OrderBy([
		    	'dimension' => new DimensionOrderBy([
		    		'dimension_name' => 'date'
		    	])
	    	])
	    ]);

        // Call the API and handle any network failures.
        try {
            $response = $client->runReport($request);
            printf('Response data: %s' . PHP_EOL, $response->serializeToJsonString());

            // $accounts = collect($response)->map(function ($account) {
            //     $accountJsonString = $account->serializeToJsonString();
            //     return json_decode($accountJsonString);
            // });

            // return $accounts;
        } catch (ApiException $ex) {
            abort(500, 'Call failed with message: %s' . $ex->getMessage());
        }
    }

    /**
     * Setup credentials for Analytics Data Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    private function setupCredentials($token)
    {
        $credentials = CredentialsWrapper::build([
            'keyFile' => [
                'type'          => 'authorized_user',
                'client_id'     => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $token['access_token'],
            ],
            'scopes'  => [
                'https://www.googleapis.com/auth/analytics',
                'https://www.googleapis.com/auth/analytics.readonly',
            ]
        ]);

        return $credentials;
    }
}
