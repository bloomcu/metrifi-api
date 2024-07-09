<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class Step2NormalizeFunnelSteps
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

        $subjectFunnelReport = GoogleAnalyticsData::funnelReport(
            connection: $analysis->subjectFunnel->connection, 
            startDate: $p['startDate'], 
            endDate: $p['endDate'],
            steps: $analysis->subjectFunnel->steps->toArray(),
        );

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
            <h1>Instructions: Normalize funnel steps</h1>

            <h2>Introduction</h2>
            <p>
            Your job is to analyze the steps of different conversion funnels and make the steps of the funnels comparable to each other. Not all funnels will have the same steps. Your task is to analyze the names of the steps and normalize them so we can compare similar parts of user experiences. When you find a step that needs to be removed from a funnel, place \"//\" in front of it.
            </p>

            <h2>Example</h2>

            <h3>Example Data</h3>
            <p>Date range: June 4 - July 1</p>

            <h4>Subject Funnel</h4>
            <p>
            <strong>Assets:</strong> $336,800 (Assets per conversion: $16,038)<br>
            <strong>Overall conversion rate:</strong> 2.30%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Auto loans page): 912 users<br>
            - Step 2 (Application start): 48 users<br>
            - Step 3 (Application complete): 21 users
            </p>

            <h4>Comparison Funnel 1</h4>
            <p>
            <strong>Assets:</strong> $271,590 (Assets per conversion: $22,633)<br>
            <strong>Overall conversion rate:</strong> 4.35%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Vehicle Loans page): 276 users<br>
            - Step 2 (App start): 46 users<br>
            - Step 3 (App complete): 12 users
            </p>

            <h4>Comparison Funnel 2</h4>
            <p>
            <strong>Assets:</strong> $571,068 (Assets per conversion: $15,863)<br>
            <strong>Overall conversion rate:</strong> 6.32%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Auto loan page): 570 users<br>
            - Step 2 (Estimate my payment page): 143 users<br>
            - Step 3 (Application start): 106 users<br>
            - Step 4 (Application complete): 36 users
            </p>

            <h3>How to Normalize the Funnels</h3>
            <p>
            First, compare the step names to identify significant differences in the steps of the different funnels.
            </p>

            <p>
            Upon reviewing the step names, most of them appear to be the same types of user experiences, except that Comparison Funnel 2 has an extra step called \"Estimate my payment\" in Step 2. To compare the funnels accurately, ignore Step 2 in Comparison Funnel 2. Below are the normalized steps with Step 2 in Comparison Funnel 2 marked with \"//\" to indicate that it should be excluded in comparison calculations.
            </p>

            <p>Date range: June 4 - July 1</p>

            <h4>Subject Funnel</h4>
            <p>
            <strong>Assets:</strong> $336,800 (Assets per conversion: $16,038)<br>
            <strong>Overall conversion rate:</strong> 2.30%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Auto loans page): 912 users<br>
            - Step 2 (Application start): 48 users<br>
            - Step 3 (Application complete): 21 users
            </p>

            <h4>Comparison Funnel 1</h4>
            <p>
            <strong>Assets:</strong> $271,590 (Assets per conversion: $22,633)<br>
            <strong>Overall conversion rate:</strong> 4.35%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Vehicle Loans page): 276 users<br>
            - Step 2 (App start): 46 users<br>
            - Step 3 (App complete): 12 users
            </p>

            <h4>Comparison Funnel 2</h4>
            <p>
            <strong>Assets:</strong> $571,068 (Assets per conversion: $15,863)<br>
            <strong>Overall conversion rate:</strong> 6.32%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Auto loan page): 570 users<br>
            // Step 2 (Estimate my payment page): 143 users<br>
            - Step 3 (Application start): 106 users<br>
            - Step 4 (Application complete): 36 users
            </p>
            -----

            Normalize the steps for the following funnels. 

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

        $response = $this->GPTService->getResponse($messageContent);

        $content = $analysis->content .= '<p><strong>Normalized steps:</strong><br>' . strip_tags($response) . '</p>';

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
