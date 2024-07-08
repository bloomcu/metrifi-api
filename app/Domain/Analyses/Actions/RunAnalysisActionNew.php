<?php

namespace DDD\Domain\Analyses\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class RunAnalysisAction
{
    use AsAction;

    protected $GPTService;

    public function __construct(GPTService $GPTService)
    {
        $this->GPTService = $GPTService;
    }

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
        // $assistantId = 'asst_yqvvZ2mCJtcvkjT6i0ozqN70'; // Funnel Analyzer V0.0.1

        $subjectFunnelReport = GoogleAnalyticsData::funnelReport(
            connection: $analysis->subjectFunnel->connection, 
            startDate: $p['startDate'], 
            endDate: $p['endDate'],
            steps: $analysis->subjectFunnel->steps->toArray(),
        );
        // return $subjectFunnelReport;

        $subjectFunnelAssets = number_format(($analysis->subjectFunnel->conversion_value / 100), 2, '.', '');

        $subjectFunnelSteps = array_map(function($step) {
            return "<li>Step {$step['order']}: {$step['name']}, {$step['users']} users ({$step['conversion']} conversion rate)</li>";
        }, $subjectFunnelReport['steps']);

        $html = "";
        foreach ($analysis->dashboard->funnels as $key => $funnel) {
            if ($key === 0) continue; // Skip subject funnel (already processed above)

            $report = GoogleAnalyticsData::funnelReport(
                connection: $funnel->connection, 
                startDate: $p['startDate'], 
                endDate: $p['endDate'],
                steps: $funnel->steps->toArray(),
            );
            // return $report;

            $assets = number_format(($funnel->conversion_value / 100), 2, '.', '');

            $steps = array_map(function($step) {
                return "<li>Step {$step['order']}: {$step['name']}, {$step['users']} users ({$step['conversion']} conversion rate)</li>";
            }, $report['steps']);

            $html .= "
                <h3>Comparison funnel {$key}: {$funnel['name']}</h3>
                <p>Assets: $ {$assets}</p>
                <p>Conversion: {$report['overallConversionRate']}%</p>
                <h4>Funnel steps:</h4>
                <ol>
                ".
                    implode('', $steps)
                ."
                </ol>
            ";
        }

        $messageContent = "
            <h1>Introduction</h1>
            <p>
            Your task is to analyze and compare website conversion funnels. You will be provided with data for a Subject Funnel (Subject) and one or more Comparison Funnels (Comparisons). Follow the stages below. <strong>IMPORTANT: DO NOT OUTPUT ANYTHING OTHER THAN THESE THREE STATEMENTS EXPLAINED IN STAGE 5.</strong>
            </p>

            <h2>Example data</h2>
            <h3>Date range: {$p['startDate']} - {$p['endDate']}</h3>

            <h4>Subject Funnel</h4>
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

        // Old
        // $threadRun = $this->createAndRunThread($assistantId, $messageContent);
        // $response = $this->retrieveFinalMessage($threadRun);

        // New
        $response = $this->GPTService->getResponse($messageContent);
        // return $response;

        // Update the analysis
        $analysis->update([
            'content' => $response,
        ]);

        return $analysis;
    }

    // private function createAndRunThread(string $assistantId, string $messageContent)
    // {
    //     return OpenAI::threads()->createAndRun([
    //         'assistant_id' => $assistantId,
    //         'thread' => [
    //             'messages' => [
    //                 [
    //                     'role' => 'user',
    //                     'content' => $messageContent,
    //                 ],
    //             ],
    //         ],
    //     ]);
    // }

    // private function retrieveThreadRun(string $threadId, string $runId)
    // {
    //     return OpenAI::threads()->runs()->retrieve($threadId, $runId);
    // }

    // private function listThreadMessages(string $threadId)
    // {
    //     return OpenAI::threads()->messages()->list($threadId);
    // }

    // private function retrieveFinalMessage(ThreadRunResponse $threadRun)
    // {
    //     while(in_array($threadRun->status, ['queued', 'in_progress'])) {
    //         $threadRun = $this->retrieveThreadRun($threadRun->threadId, $threadRun->id);
    //     }

    //     if ($threadRun->status !== 'completed') {
    //         throw new \Exception('Request failed, please try again');
    //     }

    //     $messages = $this->listThreadMessages($threadRun->threadId);
        
    //     return json_decode($messages->data[0]->content[0]->text->value);
    // }
}
