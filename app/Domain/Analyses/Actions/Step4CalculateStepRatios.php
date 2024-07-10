<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;

class Step4CalculateStepRatios
{
    use AsAction;

    protected $GPTService;

    public function __construct(GPTService $GPTService)
    {
        $this->GPTService = $GPTService;
    }

    function handle(Analysis $analysis)
    {
        $messageContent = "
            I have a job for you. Read my instructions below and then calculate ratios for the funnel steps that I provide below the instructions.

            -----

            <h1>Instructions: Calculate ratios</h1>

            <h2>Introduction</h2>
            <p>
            Your job is to calculate ratios for steps in conversion funnels. For each step, you will calculate the median conversion rate of the Comparison Funnels and then divide it by the step conversion rate in the Subject Funnel. <strong>Important:</strong> If a step I give you doesn't already have a conversion rate, then don't use it in the ratio calculations.
            </p>

            <p>
            When you see \"//\" in front of a funnel step, don't use that step in the median calculations.
            </p>

            <h2>Example</h2>

            <h3>Example Data</h3>
            <p>Date range: June 4 - July 1</p>

            <h4>Subject Funnel</h4>
            <p>
            <strong>Assets:</strong> $336,800 (Assets per conversion: $16,038)<br>
            <strong>Overall conversion rate:</strong> 2.30%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Auto loans page): 912 users. Conversion rate = 48 / 912 = 5.26%<br>
            - Step 2 (Application start): 48 users. Conversion rate = 21 / 48 = 43.75%<br>
            - Step 3 (Application complete): 21 users
            </p>

            <h4>Comparison Funnel 1</h4>
            <p>
            <strong>Assets:</strong> $271,590 (Assets per conversion: $22,633)<br>
            <strong>Overall conversion rate:</strong> 4.35%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Vehicle Loans page): 276 users. Conversion rate = 46 / 276 = 16.67%<br>
            - Step 2 (App start): 46 users. Conversion rate = 12 / 46 = 26.09%<br>
            - Step 3 (App complete): 12 users
            </p>

            <h4>Comparison Funnel 2</h4>
            <p>
            <strong>Assets:</strong> $571,068 (Assets per conversion: $15,863)<br>
            <strong>Overall conversion rate:</strong> 6.32%<br>
            <strong>Funnel steps:</strong><br>
            - Step 1 (Auto loan page): 570 users. Conversion rate = 106 / 570 = 18.60%<br>
            // Step 2 (Estimate my payment page): 143 users<br>
            - Step 3 (Application start): 106 users. Conversion rate = 36 / 106 = 33.96%<br>
            - Step 4 (Application complete): 36 users
            </p>

            <h3>Example output</h3>
            <p>
            <h4>Ratio for Step 1 of the Subject Funnel:</h4><br>
            Step 1 conversion rate of Subject Funnel = 5.26%<br>
            Step 1 conversion rate of Comparison Funnel 1 = 16.67%<br>
            Step 1 conversion rate of Comparison Funnel 2 = 18.60%<br>
            Median of Comparisons = (16.67% + 18.60%) / 2 = 17.64%<br>
            Ratio = 17.64% / 5.26% = 3.35
            </p>

            <h4>Ratio for Step 2 of the Subject Funnel:</h4><br>
            Step 2 conversion rate of Subject Funnel = 43.75%<br>
            Step 2 conversion rate of Comparison Funnel 1 = 26.09%<br>
            // Step 2 conversion rate of Comparison Funnel 2 = 33.96%<br>
            Median of Comparisons = 26.09%<sup>1</sup>
            Ratio = 26.09% / 43.75% = 0.60
            </p>

            <p>
            <sup>1</sup>The Step 2 conversion rate of Comparison Funnel 2 is skipped in this median calculation because it had \"//\" in front of it in the example data provided.
            </p>

            -----

            Calculate ratios for the step conversion rates of the following funnels. IMPORTANT: DO NOT OUTPUT ANYTHING OTHER THAN YOUR ANALYSIS AS HTML USING ONLY <p> TAG, <h4> TAG, <br> TAG AND <strong> TAG WHERE NECESSARY. NO MARKUP SYNTAX.

            {$analysis->content}
        ";

        $response = $this->GPTService->getResponse($messageContent);

        $content = $analysis->content .= '<h3>Step ratios:</h3>' . $response;

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
