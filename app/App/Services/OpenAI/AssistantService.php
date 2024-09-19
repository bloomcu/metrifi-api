<?php

namespace DDD\App\Services\OpenAI;

use OpenAI\Laravel\Facades\OpenAI;

class AssistantService
{
    public function createThread() {
        $response = OpenAI::threads()->create([]);

        return $response->toArray();
    }

    public function addMessageToThread(string $threadId, string $message, array $fileIds = []) {
        $message = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];

        foreach ($fileIds as $fileId) {
            $message['content'][] = [
                'type' => 'image_file',
                'image_file' => [
                    'file_id' => $fileId
                ]
            ];
        }

        $response = OpenAI::threads()->messages()->create($threadId, $message);

        return $response->toArray();
    }

    public function createRun(
        string $threadId, 
        string $assistantId, 
        int $maxPromptTokens = null,
        int $maxCompletionTokens = null,
    ) {
        $response = OpenAI::threads()->runs()->create(
            threadId: $threadId,
            parameters: [
                'assistant_id' => $assistantId,
                'max_completion_tokens' => $maxCompletionTokens,
                'max_prompt_tokens' => $maxPromptTokens,
            ]
        );

        return $response->toArray();
    }

    public function getRun(string $threadId, string $runId) {
        $response = OpenAI::threads()->runs()->retrieve($threadId, $runId);

        return $response->toArray();
    }

    public function getRunStatus(string $threadId, string $runId) {
        $response = OpenAI::threads()->runs()->retrieve($threadId, $runId);

        return $response->status;
    }

    public function pollRunUntilComplete(string $threadId, string $runId) {
        $maxAttempts = 40;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $run = OpenAI::threads()->runs()->retrieve($threadId, $runId);

            if ($run['status'] === 'completed') {
                return $run['status'];
            }

            if ($run['status'] === 'failed') {
                throw new \Exception("Run failed");
            }

            sleep(4);

            $attempts++;
        }

        throw new \Exception("Polling exceeded the maximum number of attempts.");
        
        // $pollingInterval = 1; // Set delay in seconds between polls
        // $maxPollingAttempts = 20; // Limit number of attempts
        // $attempts = 0;

        // do {
        //     $run = OpenAI::threads()->runs()->retrieve($threadId, $runId);

        //     if ($run->status === 'completed') break;

        //     sleep($pollingInterval);

        //     $attempts++;
        // } while ($run->status !== 'completed' && $attempts < $maxPollingAttempts);

        // return $run->toArray();
    }

    // public function pollRunForFinalMessage(string $threadId, string $runId) {
    //     $pollingInterval = 1; // Set delay in seconds between polls
    //     $maxPollingAttempts = 20; // Limit number of attempts
    //     $attempts = 0;

    //     do {
    //         $run = OpenAI::threads()->runs()->retrieve($threadId, $runId);

    //         if ($run->status === 'completed') break;

    //         sleep($pollingInterval);

    //         $attempts++;
    //     } while ($run->status !== 'completed' && $attempts < $maxPollingAttempts);

    //     $messages = $this->getMessages(threadId: $threadId);

    //     return $messages['data'][0]['content'][0]['text']['value'];
    // }

    public function getMessages(string $threadId) {
        $response = OpenAI::threads()->messages()->list($threadId);

        return $response->toArray();
    }

    public function getFinalMessage(string $threadId) {
        $messages = $this->getMessages(threadId: $threadId);

        return $messages['data'][0]['content'][0]['text']['value'];
    }

    // public function getAssistantResponse(string $assistantId, string $message) {
    //     $run = $this->createAndRunThread($assistantId, $message);

    //     $message = $this->pollForFinalMessage(
    //         threadId: $run['thread_id'], 
    //         runId: $run['id']
    //     );

    //     return $message;
    // }

    public function createAndRunThread(string $assistantId, string $message, array $fileIds = []) {
        $messages = [
            [
                'role' => 'user', 
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $message
                    ]
                ]
            ]
        ];

        foreach ($fileIds as $fileId) {
            $messages[0]['content'][] = [
                'type' => 'image_file',
                'image_file' => [
                    'file_id' => $fileId
                ]
            ];
        }

        $run = OpenAI::threads()->createAndRun([
            'assistant_id' => $assistantId,
            'thread' => [
                'messages' => $messages
            ]
        ]);

        return $run;
    }

    public function pollForFinalMessage(string $threadId, string $runId) {
        $pollingInterval = 1; // Set delay in seconds between polls
        $maxPollingAttempts = 20; // Limit number of attempts
        $attempts = 0;

        do {
            $run = OpenAI::threads()->runs()->retrieve($threadId, $runId);

            if ($run->status === 'completed') break;

            sleep($pollingInterval);

            $attempts++;
        } while ($run->status !== 'completed' && $attempts < $maxPollingAttempts);

        $messages = $this->getMessages(threadId: $threadId);

        return $messages['data'][0]['content'][0]['text']['value'];
    }

    public function uploadFile(string $fileUrl) {
        $response = OpenAI::files()->upload([
            'purpose' => 'vision',
            'file' => fopen($fileUrl, 'r')
        ]);

        return $response->toArray();
    }

    // public function getSingleMessage(string $threadId, string $messageId) {
    //     $response = OpenAI::threads()->messages()->retrieve($threadId, $messageId);

    //     return $response->toArray();
    //     return $response['data']['messages'][0]['content'];
    // }
}
