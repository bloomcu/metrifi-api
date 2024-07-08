<?php

namespace DDD\Domain\Analyses\Actions;

use OpenAI\Responses\Threads\Runs\ThreadRunResponse;
use OpenAI\Laravel\Facades\OpenAI;
use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class AnalyzeBiggestOpportunity
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
            Your task is to analyze and compare website conversion funnels. You will be provided with data for a Subject Funnel (Subject) and one or more Comparison Funnels (Comparisons). Follow the stages below. <strong>IMPORTANT: DO NOT OUTPUT ANYTHING OTHER THAN THESE THREE STATEMENTS EXPLAINED IN STAGE 5.</strong>
            </p>

            <h2>Example data</h2>

            <h3>Date range: June 4 - July 1</h3>

            <h4>Subject Funnel</h4>
            <p>
            Overall conversion rate: 2.30%<br>
            Funnel steps:<br>
            - Step 1 (Auto loans page): 912 users<br>
            - Step 2 (Application start): 48 users (5.26% conversion rate)<br>
            - Step 3 (Application complete): 21 users (43.75% conversion rate)
            </p>

            <h4>Comparison Funnel 1</h4>
            <p>
            Overall conversion rate: 4.35%<br>
            Funnel steps:<br>
            - Step 1 (Vehicle Loans page): 276 users<br>
            - Step 2 (App start): 46 users (16.67% conversion rate)<br>
            - Step 3 (App complete): 12 users (26.09% conversion rate)
            </p>

            <h4>Comparison Funnel 2</h4>
            <p>
            Overall conversion rate: 6.32%<br>
            Funnel steps:<br>
            - Step 1 (Auto loan page): 570 users<br>
            - Step 2 (Lead capture page): 143 users (25.09% conversion rate)<br>
            - Step 3 (Application start): 106 users (74.13% conversion rate)<br>
            - Step 4 (Application complete): 36 users (33.96% conversion rate)
            </p>

            <h2>Stages</h2>

            <h3>Stage 1</h3>
            <p>Observe the conversion rate of each step in each funnel.</p>

            <h4>Example conversion rates for Stage 1</h4>

            <h5>Subject Funnel</h5>
            <p>
            Step 1: 48 / 912 = 5.26%<br>
            Step 2: 21 / 48 = 43.75%
            </p>

            <h5>Comparison Funnel 1</h5>
            <p>
            Step 1: 46 / 276 = 16.67%<br>
            Step 2: 12 / 46 = 26.09%
            </p>

            <h5>Comparison Funnel 2</h5>
            <p>
            Step 1: 143 / 570 = 25.09%<br>
            Step 2: 106 / 143 = 74.13%<br>
            Step 3: 36 / 106 = 33.96%
            </p>

            <h3>Stage 2</h3>
            <p>
            Identify the step in the Subject Funnel with the Biggest Opportunity for Improvement (BOFI). The BOFI is the step with the lowest conversion rate relative to the Comparisons.
            </p>
            <p>
            Note: Ensure you compare similar steps across funnels by analyzing step names.
            </p>

            <h4>Example comparison for Stage 2</h4>

            <h5>Subject Funnel</h5>
            <p>
            - Step 1 (Auto loans page): 912 users<br>
            - Step 2 (Application start): 48 users<br>
            - Step 3 (Application complete): 21 users
            </p>

            <h5>Comparison Funnel 1</h5>
            <p>
            - Step 1 (Vehicle Loans page): 276 users<br>
            - Step 2 (App start): 46 users<br>
            - Step 3 (App complete): 12 users
            </p>

            <h5>Comparison Funnel 2</h5>
            <p>
            - Step 1 (Auto loan page): 570 users<br>
            - Step 2 (Lead capture page): 143 users<br>
            - Step 3 (Application start): 106 users<br>
            - Step 4 (Application complete): 36 users
            </p>

            <p>
            To standardize steps, exclude Step 2 from Comparison Funnel 2 and recalculate its conversion rates:
            </p>
            <p>
            - Step 1: 106 / 570 = 18.60%<br>
            - Step 2: 36 / 106 = 33.96%
            </p>

            <h5>Calculate ratios</h5>
            <p>
            <b>Step 1:</b><br>
            Subject: 5.26%<br>
            Comparison 1: 16.67%<br>
            Comparison 2: 18.60%<br>
            Median: (16.67% + 18.60%) / 2 = 17.64%<br>
            Ratio: 17.64% / 5.26% = 3.35
            </p>
            <p>
            <b>Step 2:</b><br>
            Subject: 43.75%<br>
            Comparison 1: 26.09%<br>
            Comparison 2: 33.96%<br>
            Median: (26.09% + 33.96%) / 2 = 30.03%<br>
            Ratio: 30.03% / 43.75% = 0.69
            </p>
            <p>
            The BOFI is Step 1 with a ratio of 3.35.
            </p>
            <p>
            Percentage Higher/Lower = ((5.26% - 17.64%) / 17.64%) * 100 = -70.18%
            </p>

            <h3>Stage 3</h3>
            <p>
            Output the following statement: '[BOFI step number from Stage 2] of your funnel is [percentage higher/lower from Stage 2] [higher/lower] than comparisons ([BOFI comparisons median] median)'
            </p>
            <p>
            Notes: <strong>DO NOT OUTPUT ANYTHING OTHER THAN THIS STATEMENT AS RAW TEXT.</strong>
            </p>

            <h4>Example output</h4>
            <p>
            Step 1 of your funnel is -70.18% lower than comparisons (17.64% median)
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
