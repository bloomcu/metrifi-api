<?php

namespace DDD\App\Services\Grok;

use OpenAI;

class GrokService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::factory()
          ->withApiKey(config('services.grok.api_key'))
          ->withBaseUri('https://api.x.ai/v1')
          ->make();
    }

    public function chat(
      string $instructions, 
      string $message, 
    )
    {
      $completion = $this->client->chat()->create([
        'model' => 'grok-beta',
        'messages' => [
          [
            'role' => 'system',
            'content' => $instructions,
          ],
          [
            'role' => 'user',
            'content' => $message,
          ]
        ]
      ]);

      return $completion->choices[0]->message->content;
    }
}