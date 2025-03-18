<?php

namespace DDD\Http\Services\WordPress;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Exception;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;
use Carbon\Carbon;

class WordPressPageController extends Controller
{
    public function store(Organization $organization, Request $request)
    {
        $connection = $organization->connections()->where('service', 'WordPress Website')->first();

        if (!$connection) {
            return response()->json([
                'message' => 'WordPress connection not found'
            ], 404);
        }

        try {
            // Get token data from connection
            $wordpressUrl = $connection->token['wordpress_url'];
            $username = $connection->token['username'];
            $appPassword = $connection->token['app_password'];

            // Ensure the URL has the correct API endpoint
            $apiEndpoint = '/wp-json/metrifi/v1/create-page';
            $wordpressApiUrl = rtrim($wordpressUrl, '/') . $apiEndpoint;

            // Get page title from request
            $pageTitle = $request->input('title');
            
            // Format the title with current date
            $formattedTitle = $pageTitle . ' - ' . Carbon::now()->toDateString();

            // Get blocks from request and parse them
            $blocks = $request->input('blocks', []);

            if (empty($blocks)) {
                return response()->json([
                    'message' => 'No valid blocks to send to WordPress'
                ], 400);
            }

            // Prepare the request data
            $postData = [
                'title' => $formattedTitle,
                'status' => 'draft',
                'acf' => [
                    'content_blocks' => $blocks
                ]
            ];

            // Create Basic Auth header
            $authString = base64_encode($username . ':' . $appPassword);

            // Make the request to WordPress
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authString
            ])
            ->timeout(30)
            ->post($wordpressApiUrl, $postData);

            // Check if the response is successful
            if (!$response->successful() || !isset($response['link'])) {
                throw new Exception('WordPress API returned an invalid response: ' . $response->body());
            }

            // Return the successful response
            return response()->json([
                'message' => 'WordPress page created successfully',
                'page_url' => $response['link'],
                'response' => $response->json()
            ]);

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $statusCode = 500;

            // Handle different error types
            if (strpos($errorMessage, 'Connection timeout') !== false) {
                $errorMessage = 'Connection timeout: WordPress server took too long to respond. Please try again later.';
            } elseif (strpos($errorMessage, 'cURL error 6') !== false || strpos($errorMessage, 'Could not resolve host') !== false) {
                $errorMessage = 'Network error: Unable to connect to WordPress. Please check the WordPress URL and try again.';
                $statusCode = 400;
            } elseif ($e instanceof \Illuminate\Http\Client\RequestException) {
                $statusCode = $e->getCode();
                if ($statusCode === 401 || $statusCode === 403) {
                    $errorMessage = 'Authentication error: WordPress credentials are invalid or expired.';
                } elseif ($statusCode === 404) {
                    $errorMessage = 'WordPress API endpoint not found. Please check the WordPress URL.';
                } elseif ($statusCode >= 500) {
                    $errorMessage = "WordPress server error ($statusCode): The server encountered an issue. Please try again later.";
                }
            }

            return response()->json([
                'message' => 'Failed to create WordPress page',
                'error' => $errorMessage
            ], $statusCode);
        }
    }
}