<?php

namespace DDD\App\Services\Google;

use Google\Service\Oauth2;
use Google\Client;
use DDD\Domain\Connections\Connection;
use DDD\Domain\Base\Users\User;

class GoogleAuthService
{
    private $client;
    
    public function __construct() 
    {
        $this->client = new Client();

        $this->client->setAuthConfig([
            'web' => [
                'client_id' => config('services.google.client_id'),
                'project_id' => config('services.google.project_id'),
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_secret' => config('services.google.client_secret'),
                'redirect_uris' => [config('services.google.redirect_uri')],
                'javascript_origins' => [config('services.google.javascript_origins')],
        ]]);
    }

    public function getAuthUrl(): string
    {
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
        $this->client->addScope('https://www.googleapis.com/auth/userinfo.email');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setIncludeGrantedScopes(true);

        /**
         * Return URL to Google's OAuth 2.0 server
         * 
         * This occurs when your application first needs to access the user's data. 
         * With incremental authorization, again for additional resources.
         */
        return $this->client->createAuthUrl();
    }

    public function addScope(string $scope): GoogleAuthService
    {
        $this->client->addScope($scope);

        return $this;
    }

    public function getAccessToken($code)
    {
        /**
         * Handle the OAuth 2.0 server response
         * 
         * Exchange authorization code for access token
         * If the user approves the access request, then the response contains an authorization code. 
         * If the user does not approve the request, the response contains an error message. 
         */
        try {
            return $this->client->fetchAccessTokenWithAuthCode($code);
            
        } catch (\Throwable $exception) {
            return $exception;
        }
    }

    // public function storeCredentials($code): Connection | \Throwable
    // {
    //     /**
    //      * Handle the OAuth 2.0 server response
    //      * 
    //      * Exchange authorization code for access token
    //      * If the user approves the access request, then the response contains an authorization code. 
    //      * If the user does not approve the request, the response contains an error message. 
    //      */
    //     try {
    //         $token = $this->client->fetchAccessTokenWithAuthCode($code);

    //         $connection = Connection::firstOrCreate(
    //             [
    //                 'user_id' => auth()->id()
    //             ],
    //             [
    //                 'user_id' => auth()->id(),
    //                 'token' => $token,
    //                 'google_email' => $this->getGoogleUserEmail(), 
    //             ]
    //         );

    //         return $connection;
    //     } catch (\Throwable $exception) {
    //         return $exception;
    //     }
    // }

    // private function getGoogleUserEmail(): string
    // {
    //     try {
    //         $oauth = new Oauth2($this->client);

    //         $oauthUser = $oauth->userinfo->get();

    //         return $oauthUser->email;
    //     } catch (\Throwable $exception) {
    //         return $exception;
    //     }
    // }

    // public function getGoogleUserCredentials(User $user): Connection
    // {
    //     $connection = Connection::where('user_id', $user->id)->first();

    //     $this->client->setAccessToken($connection->token);

    //     if ($this->client->isAccessTokenExpired()) {
    //         $this->refreshAccessToken($connection);
    //     }

    //     return $connection;
    // }

    private function refreshAccessToken(Connection $connection)
    {
        // Fetch new access token
        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
        $this->client->setAccessToken($this->client->getAccessToken());

        // Save new access token
        $connection->token = $this->client->getAccessToken();
        $connection->save();
    }
}
