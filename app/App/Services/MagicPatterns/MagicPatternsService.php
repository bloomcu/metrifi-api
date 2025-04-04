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

            // Log::info('Magic Patterns service response: ' . json_encode($data));

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

            $noValidComponentError = 'Magic Patterns Service: No valid component source files found in the /components directory or App.tsx. Time: ' . rand(1, 1000000000) ;
            Log::error($noValidComponentError);
            throw new \Exception($noValidComponentError);

            return;
        }

        // Throw an exception if the request failed
        $apiRequestFailedError = 'Magic Patterns Service: Magic Patterns API request failed: ' . $response->status() . ' - ' . $response->body();
        Log::error($apiRequestFailedError);
        throw new \Exception($apiRequestFailedError);

        return;
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

        // Pluck out files in /components
        foreach ($sourceFiles as $file) {
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

        // If no components are found, pluck out the App.tsx file
        if (empty($components)) {
            foreach ($sourceFiles as $file) {
                if (
                    $file['type'] === 'javascript' &&
                    !$file['isReadOnly'] &&
                    str_contains($file['name'], 'App.tsx')
                ) {
                    $components[] = [
                        'id' => $file['id'] ?? null,
                        'name' => $file['name'] ?? null,
                        'code' => $file['code'] ?? null,
                    ];
                }
            }
        }

        // If no components are found, throw an exception
        if (empty($components)) {
            $noValidComponentError = 'Magic Patterns: No usable code found in Magic Patterns response components or app.tsx';
            Log::error($noValidComponentError);
            throw new \Exception($noValidComponentError);
        }

        return $components;
    }
}