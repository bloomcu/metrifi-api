<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Services\OpenAI\GPTService;

class Step5AnalyzeBiggestOpportunity
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

            {$analysis->content}
        ";

        $response = $this->GPTService->getResponse($messageContent);

        $content = $analysis->content .= '<h3>Biggest opportunity:</h3>' . $response;

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
