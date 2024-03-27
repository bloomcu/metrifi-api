<?php
// TODO: Maybe move this to the Google Analytics service folder

namespace DDD\App\Services\GoogleAnalyticsAdmin;

use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\ApiException;
use Google\Analytics\Admin\V1beta\AnalyticsAdminServiceClient;

class GoogleAnalyticsAdminService
{
    /**
     * List Google Analytics 4 accounts
     * 
     * https://cloud.google.com/php/docs/reference/analytics-admin/latest/V1beta.AnalyticsAdminServiceClient#_Google_Analytics_Admin_V1beta_AnalyticsAdminServiceClient__listAccounts__
     * https://github.com/googleapis/php-analytics-admin
     * https://developers.google.com/analytics/devguides/config/admin/v1/client-libraries
     */
    public function listAccounts($token)
    {
        $client = new AnalyticsAdminServiceClient(['credentials' => $this->setupCredentials($token)]);

        try {
            $response = $client->listAccountSummaries();

            $accounts = collect($response)->map(function ($account) {
                $accountJsonString = $account->serializeToJsonString();
                return json_decode($accountJsonString);
            });

            return $accounts;
        } catch (ApiException $ex) {
            abort(500, 'Call failed with message: %s' . $ex->getMessage());
        }
    }

    /**
     * Setup credentials for Analytics Admin Client
     * 
     * https://stackoverflow.com/questions/73334495/how-to-use-access-tokens-with-google-admin-api-for-ga4-properties 
     */
    // TODO: Should this be a constructor, or a standalone class or helper?
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
