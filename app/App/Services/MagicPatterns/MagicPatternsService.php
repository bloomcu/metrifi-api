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
      $this->baseUrl = 'https://api.magicpatterns.com/api';
    }

    public function createDesign(
      string $prompt,
      string $designSystem = 'html',
      string $styling = 'tailwind',
      ?string $imagePath = null,
      bool $shouldAwaitGenerations = true,
      bool $requestSummary = false,
      int $numberOfGenerations = 1
  ) {
      // Prepare the base request data
      $data = [
          'prompt' => $prompt,
          'designSystem' => $designSystem,
          'styling' => $styling,
          'shouldAwaitGenerations' => $shouldAwaitGenerations,
          'requestSummary' => $requestSummary,
          'numberOfGenerations' => $numberOfGenerations,
      ];

      // Prepare the HTTP request
      $request = Http::asMultipart()
          ->baseUrl($this->baseUrl)
          ->withHeaders([
              'x-mp-api-key' => $this->apiKey,
          ]);

      // Add all data fields
      foreach ($data as $key => $value) {
          $request->attach($key, $value);
      }

      // Attach image file if provided
      if ($imagePath) {
          $request->attach(
              'image',
              file_get_contents($imagePath),
              basename($imagePath)
          );
      }

      // Send the request
      $response = $request->post('/pattern');

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