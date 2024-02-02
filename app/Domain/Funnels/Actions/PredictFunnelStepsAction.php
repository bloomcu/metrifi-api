<?php

namespace DDD\Domain\Funnels\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Support\Facades\Storage;
use DDD\Domain\Pages\Page;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class PredictFunnelStepsAction
{
    use AsAction;

    /**
     * @param  Page  $page
     * @return string
     */
    function handle(Funnel $funnel, string $terminalPagePath)
    {   
        $file = $this->generateFile($funnel);

        $assistantId = 'asst_umtD7i5B9n5rL5jbKP1UkFE3'; // Funnel Maker Assistant
        // $assistantId = 'asst_zjutsMhDsZfywxHj3q4hYB5R'; // V0.3.14 - TPP Funnel Maker (Stable API version)
        $messageContent = 'Terminal Page Path: "' . $terminalPagePath . '"; File ID: "' . $file->id . '"';
        // $assistantId = 'asst_c3sNfaAdIsE1UJaNZSHhhZXy'; // Test Assistant
        // $messageContent = 'Hello Mr. Assistant.';

        $threadRun = $this->createAndRunThread($assistantId, $messageContent);
        
        return $this->retrieveFinalMessage($threadRun);
    }

    private function fetchPageViewsAsJson(Funnel $funnel)
    {
        $report = GoogleAnalyticsData::fetchPageViews(
            connection: $funnel->connection, 
            startDate: '28daysAgo',
            endDate: 'today',
            pagePaths: null,
        );

        return json_encode($report, JSON_PRETTY_PRINT);
    }

    private function generateFile(Funnel $funnel)
    {
        try {
            $filename = $funnel->connection->name . ' - pageviews.json';

            Storage::disk('local')->put($filename, $this->fetchPageViewsAsJson($funnel));

            return OpenAI::files()->upload([
                'purpose' => 'assistants',
                'file' => fopen(storage_path('app/' . $filename), 'rb')
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to upload file');
        }
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

        return $messages->data[0]->content[0]->text->value;
    }
}