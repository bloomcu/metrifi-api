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
        $this->baseUrl = 'https://api.magicpatterns.com/api/v2';
    }

    public function createDesign(
        string $prompt,
        string $presetId = 'html-tailwind',
    ) {
        // Prepare the multipart data array
        $multipartData = [
            [
                'name' => 'prompt',
                'contents' => $prompt,
            ],
            // [
            //     'name' => 'presetId',
            //     'contents' => $presetId,
            // ],
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

            // Extract the component source file (e.g., App.tsx or a component file)
            $componentFile = $this->extractComponentSourceFile($data['sourceFiles'] ?? []);

            if ($componentFile) {
                return [
                    'id' => $data['id'] ?? null,
                    'componentCode' => $componentFile['code'] ?? null,
                    'componentName' => $componentFile['name'] ?? null,
                    'editorUrl' => $data['editorUrl'] ?? null,
                    'previewUrl' => $data['previewUrl'] ?? null,
                ];
            }

            throw new \Exception('No valid component source file found in the API response.');
        }

        // Throw an exception if the request failed
        throw new \Exception(
            'Magic Patterns API request failed: ' .
            $response->status() .
            ' - ' .
            $response->body()
        );
    }

    /**
     * Extract the main component source file from the array of source files.
     *
     * @param array $sourceFiles
     * @return array|null
     */
    protected function extractComponentSourceFile(array $sourceFiles): ?array
    {
        foreach ($sourceFiles as $file) {
            // Look for a file that is a React component (e.g., App.tsx or files in components/)
            if (
                $file['type'] === 'javascript' &&
                !$file['isReadOnly'] &&
                (str_contains($file['name'], 'App.tsx') || str_contains($file['name'], 'components/'))
            ) {
                return $file;
            }
        }

        // Fallback: return the first non-read-only JavaScript file if no clear component is found
        foreach ($sourceFiles as $file) {
            if ($file['type'] === 'javascript' && !$file['isReadOnly']) {
                return $file;
            }
        }

        return null;
    }
}