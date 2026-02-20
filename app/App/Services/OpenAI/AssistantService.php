<?php

namespace DDD\App\Services\OpenAI;

/**
 * @deprecated Use Neuron AI agents instead. This service wraps the deprecated OpenAI Assistants API.
 * @see DDD\App\Neuron\Agents\Recommendations
 */
use OpenAI\Laravel\Facades\OpenAI;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Exception;

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
        ?int $maxPromptTokens = null,
        ?int $maxCompletionTokens = null,
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
                throw new \Exception("OpenAI Assistant run failed");
            }

            sleep(4);

            $attempts++;
        }

        throw new \Exception("OpenAI Assistant polling exceeded the maximum number of attempts.");
    }

    public function getMessages(string $threadId) {
        $response = OpenAI::threads()->messages()->list($threadId);

        return $response->toArray();
    }

    public function getFinalMessage(string $threadId) {
        $messages = $this->getMessages(threadId: $threadId);

        if (empty($messages['data']) || !isset($messages['data'][0]['content'][0]['text']['value'])) {
            return '';
        }

        return $messages['data'][0]['content'][0]['text']['value'];
    }

    // Get all messages as a single string
    public function getMessagesAsString(string $threadId) {
        $messages = $this->getMessages(threadId: $threadId);
        $messageString = '';

        if (!empty($messages['data'])) {
            foreach ($messages['data'] as $message) {
                if (isset($message['content'][0]['text']['value'])) {
                    $messageString .= $message['content'][0]['text']['value'] . ' ';
                }
            }
        }

        return $messageString;
    }

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

        if (empty($messages['data']) || !isset($messages['data'][0]['content'][0]['text']['value'])) {
            return '';
        }

        return $messages['data'][0]['content'][0]['text']['value'];
    }

    public function uploadFile(string $url, string $name, string $extension) {
        $tempImagePath = null;
        
        try {
            // Download the image
            $client = new Client();
            $response = $client->get($url);
            $imageContent = $response->getBody()->getContents();

            // Save the image temporarily
            $tempImagePath = storage_path('app/' . $name . '_' . uniqid() . '.' . $extension);
            file_put_contents($tempImagePath, $imageContent);

            $response = OpenAI::files()->upload([
                'purpose' => 'vision',
                'file' => fopen($tempImagePath, 'r')
            ]);

            // Clean up and delete the temporary file
            if ($tempImagePath && file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }

            return $response->id;
        } catch (ClientException | RequestException $e) {
            // Clean up temporary file if it was created
            if ($tempImagePath && file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }
            
            // Log the error details for debugging
            Log::warning("Failed to download or upload file from URL: {$url}", [
                'error' => $e->getMessage(),
                'statusCode' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
            ]);

            // Throw a more specific exception that can be caught and handled gracefully
            throw new Exception("Failed to download image from screenshot service: " . $e->getMessage(), 0, $e);
        } catch (Exception $e) {
            // Clean up temporary file if it was created
            if ($tempImagePath && file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }
            
            // Log the error details
            Log::error("OpenAI API error:", ['errorData' => $e]);

            throw $e;
        }
    }
}
