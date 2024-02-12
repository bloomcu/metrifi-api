<?php

namespace DDD\Domain\Funnels\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Storage;
use DDD\Domain\Pages\Page;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class GetOutboundLinks
{
    use AsAction;

    // TODO: This whole action needs to be refactored as an assistant model

    /**
     * @param  Page  $page
     * @return string
     */
    function handle(Connection $connection, string $terminalPagePath)
    {   
        $file = $this->generateFile($connection);

        $assistantId = 'asst_T1e6qxJRAwRaIuHDYbfA7hRU'; // Outbound URL Finder V0.1.1
        $messageContent = 'Page Path: "' . $terminalPagePath . '"';

        $threadRun = $this->createAndRunThread($assistantId, $messageContent, $file->id);
        
        return $this->retrieveFinalMessage($threadRun);
    }

    private function generateFile(Connection $connection)
    {
        try {
            $filename = $connection->name . ' - outboundLinks.json';

            Storage::disk('local')->put('private/' . $filename, $this->fetchPageViewsAsJson($connection));

            return OpenAI::files()->upload([
                'purpose' => 'assistants',
                'file' => fopen(storage_path('app/private/' . $filename), 'rb')
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to upload file');
        }
    }

    private function fetchPageViewsAsJson(Connection $connection)
    {
        $report = GoogleAnalyticsData::fetchOutboundClicks(
            connection: $connection, 
            startDate: '28daysAgo',
            endDate: 'today',
        );

        return json_encode($report, JSON_PRETTY_PRINT);
    }

    private function createAndRunThread(string $assistantId, string $messageContent, string $fileId)
    {
        return OpenAI::threads()->createAndRun([
            'assistant_id' => $assistantId,
            'thread' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $messageContent,
                        'file_ids' => [$fileId]
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
            $threadRun = $this->retrieveThreadRun($threadRun->threadId, $threadRun->id);
        }

        if ($threadRun->status !== 'completed') {
            throw new \Exception('Request failed, please try again');
        }

        $messages = $this->listThreadMessages($threadRun->threadId);

        return json_decode($messages->data[0]->content[0]->text->value);
    }
}