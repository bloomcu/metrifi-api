<?php

namespace DDD\Domain\Funnels\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Pages\Page;

class GetEndpointSegments
{
    use AsAction;

    // TODO: This whole action needs to be refactored as an assistant model

    /**
     * @param  Page  $page
     * @return string
     */
    function handle(string $terminalPagePath)
    {   
        $assistantId = 'asst_BsM3epJYI7izJiTlOGs52bfl'; // Segmenter V0.1.3
        $messageContent = 'Terminal Page Path: "' . $terminalPagePath . '"';

        $threadRun = $this->createAndRunThread($assistantId, $messageContent);
        
        return $this->retrieveFinalMessage($threadRun);
    }

    private function createAndRunThread(string $assistantId, string $messageContent)
    {
        return OpenAI::threads()->createAndRun([
            'assistant_id' => $assistantId,
            'thread' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $messageContent,
                    ],
                ],
            ],
        ]);
    }

    private function retrieveThreadRun(string $threadId, string $runId)
    {
        return OpenAI::threads()->runs()->retrieve($threadId, $runId);
    }

    private function listThreadMessages(string $threadId)
    {
        return OpenAI::threads()->messages()->list($threadId);
    }

    private function retrieveFinalMessage(ThreadRunResponse $threadRun)
    {
        while(in_array($threadRun->status, ['queued', 'in_progress'])) {
            usleep(500000); // Sleep for 0.5 seconds (500,000 microseconds)
            $threadRun = $this->retrieveThreadRun($threadRun->threadId, $threadRun->id);
        }

        if ($threadRun->status !== 'completed') {
            throw new \Exception('Request failed, please try again');
        }

        $messages = $this->listThreadMessages($threadRun->threadId);

        return json_decode($messages->data[0]->content[0]->text->value);
    }
}