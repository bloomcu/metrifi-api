<?php

namespace DDD\App\Services\MagicPatterns;

use Illuminate\Support\Facades\Http;

class MagicPatternsService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.magicpatterns.api_key');
        $this->baseUrl = 'https://api.magicpatterns.com/api'; // Ensure this is correct
    }

    public function createDesign(
        string $prompt,
        string $designSystem = 'html',
        string $styling = 'tailwind',
        ?string $imagePath = null,
        bool $shouldAwaitGenerations = false,
        bool $requestSummary = false,
        int $numberOfGenerations = 1
    ) {
        // Prepare the multipart data array
        $multipartData = [
            [
                'name' => 'prompt',
                'contents' => $prompt,
            ],
            [
                'name' => 'designSystem',
                'contents' => $designSystem,
            ],
            [
                'name' => 'styling',
                'contents' => $styling,
            ],
            [
                'name' => 'shouldAwaitGenerations',
                'contents' => $shouldAwaitGenerations ? 'true' : 'false',
            ],
            [
                'name' => 'requestSummary',
                'contents' => $requestSummary ? 'true' : 'false',
            ],
            [
                'name' => 'numberOfGenerations',
                'contents' => (string) $numberOfGenerations,
            ],
        ];

        // Add image if provided
        if ($imagePath && file_exists($imagePath)) {
            $multipartData[] = [
                'name' => 'image',
                'contents' => fopen($imagePath, 'r'),
                'filename' => basename($imagePath),
            ];
        }

        // Send the request with increased timeout and retries
        $response = Http::asMultipart()
            ->baseUrl($this->baseUrl)
            ->withHeaders([
                'x-mp-api-key' => $this->apiKey,
            ])
            ->timeout(60) // Increase timeout to 60 seconds
            ->retry(3, 1000) // Retry 3 times with 1-second delay between attempts
            ->post('/pattern', $multipartData);

        // Check if the request was successful
        if ($response->successful()) {
            return $response->json();
        }

        // Throw an exception if the request failed
        throw new \Exception(
            'Magic Patterns API request failed: ' . 
            $response->status() . 
            ' - ' . 
            $response->body()
        );
    }
}