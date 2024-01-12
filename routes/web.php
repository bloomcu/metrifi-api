<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Builder;
// use Google\Analytics\Admin\V1beta\AnalyticsAdminServiceClient;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $clientId = env("GOOGLE_CLIENT_ID");
    $clientSecret = env("GOOGLE_CLIENT_SECRET");
    $redirectUri = env("GOOGLE_REDIRECT_URI");

    $client = new Google\Client();
    $config = [
        'web' => [
            'client_id' => $clientId,
            'project_id' => 'bloomcu-community-analytics',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_secret' => $clientSecret,
            'redirect_uris' => [
                "http://127.0.0.1:8000/auth/google/callback","http://localhost","http://127.0.0.1:8000"
            ],
            'javascript_origins' => ['https://localhost:3000']
    ]];

    /**
     * Create the authorization request
     * 
     * The request defines permissions the user will be asked to grant you.
     * Using 'offline' will give you both an access and refresh token.
     * Using 'consent' will prompt the user for consent.
     * Using 'setIncludeGrantedScopes' enables incremental auth.
     * 
     * https://developers.google.com/identity/protocols/oauth2/web-server
     */
    $client->setAuthConfig($config);
    $client->addScope(Google\Service\Analytics::ANALYTICS_READONLY);
    $client->setRedirectUri($redirectUri);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setIncludeGrantedScopes(true);

    /**
     * Redirect to Google's OAuth 2.0 server
     * 
     * This occurs when your application first needs to access the user's data. 
     * With incremental authorization, again for additional resources.
     */
    $auth_url = $client->createAuthUrl();

    return redirect($auth_url);
});

Route::get('/auth/google/callback', function () {
    $clientId = env("GOOGLE_CLIENT_ID");
    $clientSecret = env("GOOGLE_CLIENT_SECRET");
    $redirectUri = env("GOOGLE_REDIRECT_URI");

    $client = new Google\Client();
    $client->setAuthConfig([
        'web' => [
            'client_id' => $clientId,
            'project_id' => 'bloomcu-community-analytics',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_secret' => $clientSecret,
            'redirect_uris' => [
                "http://127.0.0.1:8000/auth/google/callback","http://localhost","http://127.0.0.1:8000"
            ],
            'javascript_origins' => ['https://localhost:3000']
    ]]);

    /**
     * Handle the OAuth 2.0 server response
     * 
     * If the user approves the access request, then the response contains an authorization code. 
     * If the user does not approve the request, the response contains an error message. 
     */

    // TODO: Catch errors, eg "../auth/google/callback?error=access_denied"

    // Exchange access code for access token
    try {
        $access_token = $client->fetchAccessTokenWithAuthCode(request()->input('code'));
        $client->setAccessToken($access_token);
        dd($access_token);
    } catch (Throwable $exception) {
        dd($exception);
    }
});

Route::get('/analytics/admin/accounts', function () {    
    $clientId = env("GOOGLE_CLIENT_ID");
    $clientSecret = env("GOOGLE_CLIENT_SECRET");
    $redirectUri = env("GOOGLE_REDIRECT_URI");

    /**
     * Setup credentials for Analytics Admin Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    $credentials = Google\ApiCore\CredentialsWrapper::build([
        'keyFile' => [
            'type'          => 'authorized_user',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => ''
        ],
        'scopes'  => [
            'https://www.googleapis.com/auth/analytics',
            'https://www.googleapis.com/auth/analytics.readonly',
        ]
    ]);

    /**
     * List Google Analytics 4 accounts
     * 
     * https://cloud.google.com/php/docs/reference/analytics-admin/latest/V1beta.AnalyticsAdminServiceClient#_Google_Analytics_Admin_V1beta_AnalyticsAdminServiceClient__listAccounts__
     * https://github.com/googleapis/php-analytics-admin
     * https://developers.google.com/analytics/devguides/config/admin/v1/client-libraries
     */
    $client = new Google\Analytics\Admin\V1beta\AnalyticsAdminServiceClient(['credentials' => $credentials]);
    $accounts = $client->listAccounts();
    dd($accounts);
});

Route::get('/analytics/admin/properties', function () {   
    $clientId = env("GOOGLE_CLIENT_ID");
    $clientSecret = env("GOOGLE_CLIENT_SECRET");
    $redirectUri = env("GOOGLE_REDIRECT_URI");

    /**
     * Setup credentials for Analytics Admin Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    $credentials = Google\ApiCore\CredentialsWrapper::build([
        'keyFile' => [
            'type'          => 'authorized_user',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => ''
        ],
        'scopes'  => [
            'https://www.googleapis.com/auth/analytics',
            'https://www.googleapis.com/auth/analytics.readonly',
        ]
    ]);

    /**
     * List Google Analytics 4 properties
     * 
     * https://cloud.google.com/php/docs/reference/analytics-admin/latest/V1beta.AnalyticsAdminServiceClient#_Google_Analytics_Admin_V1beta_AnalyticsAdminServiceClient__listProperties__
     * https://github.com/googleapis/php-analytics-admin
     * https://developers.google.com/analytics/devguides/config/admin/v1/client-libraries
     */
    $client = new Google\Analytics\Admin\V1beta\AnalyticsAdminServiceClient(['credentials' => $credentials]);
    $properties = $client->listProperties('parent:accounts/273824');
    dd($properties);
});

Route::get('/analytics/data/report', function () {    
    $clientId = env("GOOGLE_CLIENT_ID");
    $clientSecret = env("GOOGLE_CLIENT_SECRET");
    $redirectUri = env("GOOGLE_REDIRECT_URI");
    
    /**
     * Setup credentials for Analytics Data Client
     * 
     * https://cloud.google.com/php/docs/reference/analytics-data/latest
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    $credentials = Google\ApiCore\CredentialsWrapper::build([
        'keyFile' => [
            'type'          => 'authorized_user',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => ''
        ],
        'scopes'  => [
            // 'https://www.googleapis.com/auth/analytics',
            'https://www.googleapis.com/auth/analytics.readonly',
        ]
    ]);

    /**
     * Run a report
     * 
     * https://cloud.google.com/php/docs/reference/analytics-data/latest
     * https://stackoverflow.com/questions/67576611/why-does-analytics-data-api-v1-beta-not-conform-to-the-rest-spec
     * Examples: https://kdaws.com/learn/ga4-how-to-use-the-google-analytics-php-data-library/
     * Examples: https://github.com/GoogleCloudPlatform/php-docs-samples/blob/main/analyticsdata/src/run_report.php
     */
    $client = new Google\Analytics\Data\V1beta\BetaAnalyticsDataClient(['credentials' => $credentials]);

    $report = $client->runReport([
        'property' => 'properties/382835060',
        'dateRanges' => [
            // https://cloud.google.com/php/docs/reference/analytics-data/latest/V1beta.DateRange
            new Google\Analytics\Data\V1beta\DateRange([
                // 'start_date' => '2023-09-01',
                // 'end_date' => '2023-09-15'
                'start_date' => '30daysAgo',
                'end_date' => 'today'
            ])
        ],
        'dimensions' => [
            // https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema#dimensions
            new Google\Analytics\Data\V1beta\Dimension([
                'name' => 'pageTitle'
            ]),
        ],
        // 'dimensionFilter' => new Google\Analytics\Data\V1beta\FilterExpression([
        //     'filter' => new Google\Analytics\Data\V1beta\Filter([
        //         'field_name' => 'customUser:link_classes',
        //         'string_filter' => new Google\Analytics\Data\V1beta\Filter\StringFilter([
        //             'match_type' => Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType::BEGINS_WITH,
        //             'value' => 'AdvertA',
        //             'case_sensitive' => false
        //         ])
        //     ])
        // ]),
        'metrics' => [
            new Google\Analytics\Data\V1beta\Metric([
                // 'name' => 'eventCount',
                // 'name' => 'activeUsers',
                'name' => 'screenPageViews',
            ])
        ],
        'orderBys' => [
            new Google\Analytics\Data\V1beta\OrderBy([
                'metric' => new Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy([
                    'metric_name' => 'screenPageViews',
                ])
            ])
        ]
    ]);

    $results = [];
    foreach ($report->getRows() as $row) {
        array_push($results, [
            'dimension' => $row->getDimensionValues()[0]->getValue(),
            'metric' => $row->getMetricValues()[0]->getValue(),
        ]);
    }

    dd($results);
});