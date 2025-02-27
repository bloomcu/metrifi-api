<?php

namespace DDD\App\Services\Grok;

use OpenAI;
use Illuminate\Support\Facades\Log;

class GrokService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::factory()
            ->withApiKey(config('services.grok.api_key'))
            ->withBaseUri('https://api.x.ai/v1')
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 300]))
            ->make();
    }

    public function chat(
        string $instructions,
        string $message,
        ?string $responseFormat = null
    ) {
        // Default system instructions
        $systemInstructions = $instructions;

        // If a response format is specified, request both a message and structured data
        if ($responseFormat) {
            $systemInstructions .= "\n\nPlease provide your response in JSON format with two keys: 'message' (a string with your natural language response) and 'data' (the structured data as $responseFormat). Do not wrap the response in Markdown code blocks (e.g., ```json). Return only the raw JSON.";
        }

        Log::info("System instructions: \n" . $systemInstructions . "\n\n");
        Log::info("Message:  \n" . $message . "\n\n");

        $completion = $this->client->chat()->create([
            'model' => 'grok-beta',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemInstructions,
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ]
            ]
        ]);

        $rawResponse = $completion->choices[0]->message->content;

        // Log::info("Raw response: \n" . $rawResponse . "\n\n");

        // Clean the response if a format is requested
        return $responseFormat ? $this->cleanMarkdown($rawResponse) : $rawResponse;
    }

    /**
     * Remove Markdown code block syntax from the response.
     *
     * @param string $response
     * @return string
     */
    protected function cleanMarkdown(string $response): string
    {
        // Remove ```json and ``` markers, along with any surrounding whitespace
        $response = preg_replace('/^```json\s*|\s*```$/m', '', $response);
        return trim($response);
    }
}