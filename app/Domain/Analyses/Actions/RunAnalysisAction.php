<?php

namespace DDD\Domain\Analyses\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class RunAnalysisAction
{
    use AsAction;

    function handle(Analysis $analysis, string $period = 'last28Days')
    {
        // Bail early if subject funnel has no steps
        if (count($analysis->subjectFunnel->steps) === 0) {
            return;
        }

        // Bail early if dashboard has no funnels
        if (count($analysis->dashboard->funnels) === 0) {
            return;
        }

        $p = match ($period) {
            'yesterday' => [
                'startDate' => now()->subDays(1)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last7Days' => [
                'startDate' => now()->subDays(7)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last28Days' => [
                'startDate' => now()->subDays(28)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ]
        };

        // Setup assistant
        $assistantId = 'asst_yqvvZ2mCJtcvkjT6i0ozqN70'; // Funnel Analyzer V0.0.1

        $subjectFunnelReport = GoogleAnalyticsData::funnelReport(
            connection: $analysis->subjectFunnel->connection, 
            startDate: $p['startDate'], 
            endDate: $p['endDate'],
            steps: $analysis->subjectFunnel->steps->toArray(),
        );

        $subjectFunnelAssets = number_format(($analysis->subjectFunnel->conversion_value / 100), 2, '.', '');

        $subjectFunnelSteps = array_map(function($step) {
            return "<li>Step {$step['order']}: {$step['name']}, {$step['users']} users</li>";
        }, $subjectFunnelReport['steps']);

        $html = "";
        foreach ($analysis->dashboard->funnels as $key => $funnel) {
            $index = $key + 1;

            $report = GoogleAnalyticsData::funnelReport(
                connection: $funnel->connection, 
                startDate: $p['startDate'], 
                endDate: $p['endDate'],
                steps: $funnel->steps->toArray(),
            );

            $assets = number_format(($funnel->conversion_value / 100), 2, '.', '');

            $steps = array_map(function($step) {
                return "<li>Step {$step['order']}: {$step['name']}, {$step['users']} users</li>";
            }, $report['steps']);

            $html .= "
                <h3>Comparison funnel {$index}: {$funnel['name']}</h3>
                <p>Assets: $ {$assets}</p>
                <p>Conversion: {$funnel['overallConversionRate']}%</p>
                <h4>Funnel steps:</h4>
                <ol>
                ".
                    implode('', $steps)
                ."
                </ol>
            ";
        }

        $messageContent = "
            <h2>Funnel data</h2>
            <p>Time period: {$p['startDate']} - {$p['endDate']}</p>

            <h3>Subject Funnel</h3>
            <p>Assets: $ {$subjectFunnelAssets}</p>
            <p>Conversion: {$subjectFunnelReport['overallConversionRate']}%</p>
            <h4>Funnel steps:</h4>
            <ol>
            ".
                implode('', $subjectFunnelSteps)
            ."
            </ol>
            {$html}
        ";

        // return $messageContent;

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
