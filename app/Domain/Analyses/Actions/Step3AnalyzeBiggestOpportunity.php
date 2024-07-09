<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;

class Step3AnalyzeBiggestOpportunity
{
    use AsAction;

    protected $GPTService;

    public function __construct(GPTService $GPTService)
    {
        $this->GPTService = $GPTService;
    }

    function handle(Analysis $analysis, $subjectFunnelReport, $comparisonFunnelReports)
    {
        $subjectFunnelSteps = array_map(function($step) {
            return "<li>Step {$step['order']}: {$step['name']}, {$step['users']} users ({$step['conversion']} conversion rate)</li>";
        }, $subjectFunnelReport['steps']);

        // Comparison funnels
        $html = "";
        foreach ($comparisonFunnelReports as $key => $report) {
            $steps = array_map(function($step) {
                return "<li>Step {$step['order']} ({$step['name']}): {$step['users']} users ({$step['conversion']} conversion rate)</li>";
            }, $report['steps']);

            $html .= "
                <h3>Comparison funnel {$key}: {$report['funnel_name']}</h3>
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
            <p>Time period: {$report['period']}</p>

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

        $content = $analysis->content .= '<p><strong>Biggest opportunity:</strong><br>' . strip_tags($response) . '</p>';

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
