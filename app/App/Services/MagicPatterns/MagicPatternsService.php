<?php

namespace DDD\App\Services\MagicPatterns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MagicPatternsService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.magicpatterns.api_key');
        $this->baseUrl = 'https://api.magicpatterns.com/api/v2';
    }

    public function createDesign(
        string $prompt,
    ) {
        $multipartData = [
            [
                'name' => 'prompt',
                'contents' => $prompt,
            ]
        ];

        // Send the request with increased timeout and retries
        $response = Http::asMultipart()
            ->baseUrl($this->baseUrl)
            ->withHeaders([
                'x-mp-api-key' => $this->apiKey,
            ])
            ->timeout(300)
            ->retry(15, 2000)
            ->post('/pattern', $multipartData);

        // Check if the request was successful
        if ($response->successful()) {
            $data = $response->json();

            // Extract all component source files from /components directory
            $componentFiles = $this->extractComponentSourceFiles($data['sourceFiles'] ?? []);

            if (!empty($componentFiles)) {
                return [
                    'id' => $data['id'] ?? null,
                    'components' => $componentFiles, // Return an array of components
                    'editorUrl' => $data['editorUrl'] ?? null,
                    'previewUrl' => $data['previewUrl'] ?? null,
                ];
            }

            $noValidComponentError = 'Magic Patterns Service: No valid component source files found in the /components directory.';
            Log::error($noValidComponentError);
            throw new \Exception($noValidComponentError);
        }

        // Throw an exception if the request failed
        $apiRequestFailedError = 'Magic Patterns Service: Magic Patterns API request failed: ' . $response->status() . ' - ' . $response->body();
        Log::error($apiRequestFailedError);
        throw new \Exception($apiRequestFailedError);
    }

    /**
     * Extract all component source files from the /components directory.
     *
     * @param array $sourceFiles
     * @return array
     */
    protected function extractComponentSourceFiles(array $sourceFiles): array
    {
        $components = [];

        foreach ($sourceFiles as $file) {
            // Look for files in /components directory that are JavaScript and not read-only
            if (
                $file['type'] === 'javascript' &&
                !$file['isReadOnly'] &&
                str_contains($file['name'], 'components/')
            ) {
                $components[] = [
                    'id' => $file['id'] ?? null,
                    'name' => $file['name'] ?? null,
                    'code' => $file['code'] ?? null,
                ];
            }
        }

        return $components;
    }
}