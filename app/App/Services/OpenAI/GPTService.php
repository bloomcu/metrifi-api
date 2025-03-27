<?php

namespace DDD\App\Services\OpenAI;

use OpenAI;

class GPTService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::factory()
            ->withApiKey(config('services.openai.api_key'))
            ->withBaseUri('https://api.openai.com/v1')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 300]))
            ->make();
    }

    public function chat(
        string $model,
        string $instructions,
        string $message,
        ?string $responseFormat = null
    ){
        if ($responseFormat) {
            $instructions .= "\n\nCRITICAL: Return your response as JSON with this structure $responseFormat. Do not wrap the response in Markdown (e.g., ```json). Return only the raw JSON.";
        }

        $completion = $this->client->chat()->create([
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $instructions,
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
        ]);

        return $completion->choices[0]->message->content;
    }
}