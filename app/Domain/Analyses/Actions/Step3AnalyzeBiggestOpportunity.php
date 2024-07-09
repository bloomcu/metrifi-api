<?php

namespace DDD\Domain\Analyses\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class Step3AnalyzeBiggestOpportunity
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

        // $subjectFunnelAssets = number_format(($analysis->subjectFunnel->conversion_value / 100), 2, '.', '');

        $subjectFunnelSteps = array_map(function($step) {
            return "<li>Step {$step['order']}: {$step['name']}, {$step['users']} users ({$step['conversion']} conversion rate)</li>";
        }, $subjectFunnelReport['steps']);

        // Comparison funnels
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

            // $assets = number_format(($funnel->conversion_value / 100), 2, '.', '');

            $steps = array_map(function($step) {
                return "<li>Step {$step['order']} ({$step['name']}): {$step['users']} users ({$step['conversion']} conversion rate)</li>";
            }, $report['steps']);

            $html .= "
                <h3>Comparison funnel {$key}: {$funnel['name']}</h3>
                <p>Conversion: {$report['overallConversionRate']}%</p>
                <h4>Funnel steps:</h4>
                <ol>
                ".
                    implode('', $steps)
                ."
                </ol>
            ";
        } // End comparison funnels loop

        $messageContent = "
            <h1>Introduction</h1>
            <p>
            Your task is to analyze and compare website conversion funnels. You will be provided with data for a Subject Funnel (Subject) and one or more Comparison Funnels (Comparisons).
            I want to know which step in the Subject Funnel has the biggest opportunity for improvement compared to the Comparison Funnels.
            </p>

            <p>
            Notes: <strong>DO NOT OUTPUT ANYTHING OTHER THAN YOUR ANALYSIS AS HTML USING ONLY ONE <p> TAG AND <strong> TAGS WHERE NECESSARY. NO MARKUP SYNTAX.</strong>
            Begin your analysis with the following statement: \"The biggest opportunity for improvement in the Subject Funnel is at step X.\" and continue with your analysis.
            </p>

            <p>Now I will give you the data you need to complete the analysis.</p>

            <h2>Funnel data</h2>
            <p>Time period: {$p['startDate']} - {$p['endDate']}</p>

            <h3>Subject Funnel</h3>
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

        $content = $analysis->content .= '<p><strong>Biggest opportunity:</strong><br>' . strip_tags($response) . '</p>';

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
