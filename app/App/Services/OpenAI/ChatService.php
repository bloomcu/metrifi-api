<?php

namespace DDD\App\Services\OpenAI;

use OpenAI;

class GPTService
{
    protected $client;

    public function __construct()
    {
        /**
         * Docs:
         * https://medium.com/@rizky.purnawan/integrating-laravel-with-openai-assistants-threads-file-upload-and-messaging-using-gpt-4-turbo-b849391864a1
         */
        $this->client = OpenAI::client(
            config('services.openai.api_key')
        );
    }

    public function getResponse(string $prompt, string $thread = null): string
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                    'thread' => $thread,
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    public function uploadFile(string $filePath): string
    {
        $response = $this->client->files()->upload([
            'file' => fopen($filePath, 'r'),
        ]);

        return $response->id;
    }

    public function sendMessage(string $message, string $threadId): string
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message,
                    'thread' => $threadId,
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }
}