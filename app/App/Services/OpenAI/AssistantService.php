<?php

namespace DDD\App\Services\OpenAI;

use OpenAI\Laravel\Facades\OpenAI;

class AssistantService
{
    // protected $client;

    // public function __construct()
    // {
    //     $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    // }

    public function getAssistantResponse(string $assistantId, string $message) {
        $run = $this->createAndRunThread($assistantId, $message);

        return $this->getFinalMessage(
            threadId: $run['thread_id'], 
            runId: $run['id']
        );
    }

    public function createAndRunThread(string $assistantId, string $message) {
        $run = OpenAI::threads()->createAndRun([
            'assistant_id' => $assistantId,
            'thread' => [
                'messages' => [
                    [
                        'role' => 'user', 
                        'content' => $message
                    ],
                ],
            ]
        ]);

        return $run;
    }

    public function getFinalMessage(string $threadId, string $runId) {
        $pollingInterval = 1; // Set a delay in seconds between polls
        $maxPollingAttempts = 6; // Optional: limit the number of attempts
        $attempts = 0;

        do {
            $run = OpenAI::threads()->runs()->retrieve($threadId, $runId);

            if ($run->status === 'completed') {
                break;
            }

            sleep($pollingInterval);

            $attempts++;
        } while ($run->status !== 'completed' && $attempts < $maxPollingAttempts);

        $messages = $this->getMessagesList(threadId: $threadId);

        return $messages['data'][0]['content'][0]['text']['value'];
    }

    public function getThread(string $threadId) {
        $response = OpenAI::threads()->retrieve($threadId);

        return $response->toArray();
        // return $response['data']['messages'][0]['content'];
    }

    public function getMessagesList(string $threadId) {
        $response = OpenAI::threads()->messages()->list($threadId);

        return $response->toArray();
        // return $response['data']['messages'][0]['content'];
    }

    public function getMessage(string $threadId, string $messageId) {
        $response = OpenAI::threads()->messages()->retrieve($threadId, $messageId);

        return $response->toArray();
        return $response['data']['messages'][0]['content'];
    }
}
