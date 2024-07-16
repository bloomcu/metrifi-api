
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

    function handle(Analysis $analysis, $subjectFunnelReport, $comparisonFunnelReports)
    {
        $subjectFunnelSteps = array_map(function($step) {
            return "<li>Step \"{$step['name']}\": {$step['users']} users ({$step['conversion']} conversion rate)</li>";
        }, $subjectFunnelReport['steps']);

        // Comparison funnels
        $comparisonFunnels = "";
        foreach ($comparisonFunnelReports as $key => $report) {
            $steps = array_map(function($step) {
                return "<li>Step \"{$step['name']}\": {$step['users']} users ({$step['conversion']} conversion rate)</li>";
            }, $report['steps']);

            $comparisonFunnels .= "
                <h3>Comparison funnel: {$report['funnel_name']}</h3>
                <p>Conversion: {$report['overallConversionRate']}%</p>
                <h4>Funnel steps:</h4>
                <ol>
                ".
                    implode('', $steps)
                ."
                </ol>
            ";
        } // End comparison funnels loop

        /**
         * V4
         */
        // $messageContent = "
        //     I want to optimize a conversion funnel on a credit union website. Below, I've provided the current analytics of my funnel, along with funnel analytics from other credit unions for comparison. Tell me which step in my funnel I should focus on improving. Limit your analysis to 40 words.

        //     Funnel data:

        //     <p>Time period: {$report['period']}</p>
        //     <h3>Subject Funnel</h3>
        //     <p>Conversion: {$subjectFunnelReport['overallConversionRate']}%</p>
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelSteps)
        //     ."
        //     </ol>
        //     {$comparisonFunnels}
        // ";

        // /**
        //  * V6
        //  */
        // $messageContent = "
        //     Your task is to analyze and compare website conversion funnels. Below, I've provided data for a Subject Funnel and one or more Comparison Funnels. I want to know which step in the Subject Funnel has the biggest opportunity for improvement compared to the Comparison Funnels.

        //     Begin your analysis with, \"The biggest opportunity for improvement is at step …\" Complete the sentence and then continue your analysis. Limit your analysis to 40 words.

        //     Now I will give you the data you need to complete the analysis:

        //     <h2>Funnel data</h2>
        //     <p>Time period: {$report['period']}</p>

        //     <h3>Subject Funnel</h3>
        //     <p>Conversion: {$subjectFunnelReport['overallConversionRate']}%</p>
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelSteps)
        //     ."
        //     </ol>
        //     {$comparisonFunnels}
        // ";

        // /**
        //  * V6.1
        //  */
        // $messageContent = "
        //     Your task is to analyze and compare website conversion funnels. Below, I've provided data for my funnel and one or more comparison funnels. I want to know which step in my funnel has the biggest opportunity for improvement compared to the comparison funnels.

        //     Begin your analysis with, \"The biggest opportunity for improvement is at step …\" Complete the sentence and then continue your analysis. Limit your analysis to 40 words.

        //     Now I will give you the data you need to complete the analysis:

        //     <h2>Funnel data</h2>
        //     <p>Time period: {$report['period']}</p>

        //     <h3>Subject funnel:</h3>
        //     <p>Conversion: {$subjectFunnelReport['overallConversionRate']}%</p>
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelSteps)
        //     ."
        //     </ol>
        //     {$comparisonFunnels}
        // ";

        /**
         * V6.2
         */
        $messageContent = "
            Your task is to analyze and compare website conversion funnels. Below, I've provided data for my funnel and one or more comparison funnels. Calculate the conversion rate of each step in each funnel.

            Begin your analysis with, \"The biggest opportunity for improvement is…\" Limit your analysis to 40 words.

            I WANT TO KNOW WHICH TRANSITION (STEP TO STEP) IN MY FUNNEL HAS THE BIGGEST OPPORTUNITY FOR IMPROVEMENT COMPARED TO THE COMPARISON FUNNELS.

            Now I will give you the data you need to complete the analysis:

            <h2>Funnel data</h2>
            <p>Time period: {$report['period']}</p>

            <h3>Subject funnel:</h3>
            <p>Conversion: {$subjectFunnelReport['overallConversionRate']}%</p>
            <h4>Funnel steps:</h4>
            <ol>
            ".
                implode('', $subjectFunnelSteps)
            ."
            </ol>
            {$comparisonFunnels}
        ";

        // dd($messageContent);

        $response = $this->GPTService->getResponse($messageContent);

        $content = $analysis->content .= $response;

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
