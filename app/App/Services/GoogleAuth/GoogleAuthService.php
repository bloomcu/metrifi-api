<?php

namespace DDD\App\Services\GoogleAuth;

use Google\Client;
use DDD\Domain\Connections\Connection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

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

    /**
     * Chain this to add a different scope value to the request
     * 
     * E.g., GoogleAuth::addScope('https://www.googleapis.com/auth/userinfo.email')->getAuthUrl();
     */
    public function addScope(string $scope): GoogleAuthService
    {
        $this->client->addScope($scope);

        return $this;
    }

    /**
     * Chain this to add a state value to the request
     * 
     * Recommended, call the setState function. Using a state value can increase your assurance that
     * an incoming connection is the result of an authentication request.
     * 
     * E.g., GoogleAuth::setState('sample_passthrough_value')->getAuthUrl();
     */
    public function setState(string $state): GoogleAuthService
    {
        $this->client->setState($state);

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

    public function validateConnection(Connection $connection)
    {
        $storedToken = $connection->token ?? [];

        Log::debug('Validating Google Analytics connection token', [
            'connection_id' => $connection->id,
            'service' => $connection->service,
        ]);

        $this->client->setAccessToken($storedToken);

        $refreshToken = $storedToken['refresh_token'] ?? $this->client->getRefreshToken();

        if ($this->client->isAccessTokenExpired()) {
            if (!$refreshToken) {
                Log::error('Unable to refresh Google Analytics token: refresh token missing', [
                    'connection_id' => $connection->id,
                ]);
                throw new RuntimeException('Missing refresh token for Google Analytics connection.');
            }

            Log::info('Refreshing expired Google Analytics access token', [
                'connection_id' => $connection->id,
            ]);
            $newToken = $this->refreshAccessToken($refreshToken);

            $connection->token = $newToken;
            $connection->save();
            Log::info('Google Analytics access token refreshed successfully', [
                'connection_id' => $connection->id,
                'token_keys' => array_keys($newToken),
            ]);
        } elseif ($refreshToken && empty($storedToken['refresh_token'])) {
            // Token is valid but persisted credentials were missing the refresh token; persist it now.
            Log::warning('Persisting missing refresh token for Google Analytics connection', [
                'connection_id' => $connection->id,
            ]);
            $storedToken['refresh_token'] = $refreshToken;
            $connection->token = $storedToken;
            $connection->save();
        }

        return $connection->fresh();
    }

    private function refreshAccessToken(string $refreshToken): array
    {
        $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($newToken['error'])) {
            Log::error('Google Analytics refresh token request returned an error', [
                'error' => $newToken['error'],
            ]);
            throw new RuntimeException('Unable to refresh Google Analytics access token: ' . $newToken['error']);
        }

        if (empty($newToken['refresh_token'])) {
            $newToken['refresh_token'] = $refreshToken;
        }

        // Ensure the client instance has the updated token context
        $this->client->setAccessToken($newToken);

        Log::debug('Google Analytics refresh token response received', [
            'token_keys' => array_keys($newToken),
        ]);

        return $newToken;
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
}
