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

    function handle(Analysis $analysis, $subjectFunnel, $comparisonFunnels)
    {   
        // Subject funnel
        // $subjectFunnelStepsHTML = array_map(function($step) {
        //     return "<li>Step \"{$step['name']}\": {$step['users']} users ({$step['conversion']} conversion rate)</li>";
        // }, $subjectFunnel['report']['steps']);

        $subjectFunnelStepsHTML = [];
        foreach ($subjectFunnel['report']['steps'] as $index => $step) {
            $subjectFunnelStepsHTML[] = "<li>Step \"{$step['name']}\": {$step['users']} users</li>";
            // $subjectFunnelStepsHTML[] = "<li>Step \"{$step['name']}\": {$step['users']} users " . ($index != 0 ? "({$step['conversionRate']}% conversion rate)" : '') . "</li>";
            // $subjectFunnelStepsHTML[] = "<li>Step \"{$step['name']}\": {$step['users']} users " . (array_key_exists($index + 1, $subjectFunnel['report']['steps']) ? "({$subjectFunnel['report']['steps'][$index + 1]['conversionRate']}% conversion rate)" : '') . "</li>";
        }

        // // Comparison funnels
        // $comparisonFunnelsHTML = "";
        // foreach ($comparisonFunnels as $key => $comparisonFunnel) {
        //     $steps = array_map(function($step) {
        //         return "<li>Step \"{$step['name']}\": {$step['users']} users ({$step['conversion']} conversion rate)</li>";
        //     }, $comparisonFunnel['report']['steps']);

        //     $comparisonFunnelsHTML .= "
        //         <h3>Comparison funnel: {$comparisonFunnel['name']}</h3>
        //         <p>Conversion: {$comparisonFunnel['report']['overallConversionRate']}%</p>
        //         <h4>Funnel steps:</h4>
        //         <ol>
        //         ".
        //             implode('', $steps)
        //         ."
        //         </ol>
        //     ";
        // } // End comparison funnels loop

        // Comparison funnels V6.3
        $comparisonFunnelsHTML = "";
        foreach ($comparisonFunnels as $comparisonFunnel) {
            $steps = array();

            foreach ($comparisonFunnel['report']['steps'] as $index => $step) {
                $steps[] = "<li>Step \"{$step['name']}\": {$step['users']} users</li>";
                // $steps[] = "<li>Step \"{$step['name']}\": {$step['users']} users " . ($index != 0 ? "({$step['conversionRate']}% conversion rate)" : '') . "</li>";
                // $steps[] = "<li>Step \"{$step['name']}\": {$step['users']} users " . (array_key_exists($index + 1, $comparisonFunnel['report']['steps']) ? "({$comparisonFunnel['report']['steps'][$index + 1]['conversionRate']}% conversion rate)" : '') . "</li>";
            }

            $comparisonFunnelsHTML .= "
                <h3>Comparison funnel: {$comparisonFunnel['name']}</h3>
                <h4>Funnel steps:</h4>
                <ol>
                ".
                    implode('', $steps)
                ."
                </ol>
            ";
        }

        /**
         * V4
         */
        // $messageContent = "
        //     I want to optimize a conversion funnel on a credit union website. Below, I've provided the current analytics of my funnel, along with funnel analytics from other credit unions for comparison. Tell me which step in my funnel I should focus on improving. Limit your analysis to 40 words.

        //     Funnel data:

        //     <p>Time period: {$report['period']}</p>
        //     <h3>Subject Funnel</h3>
        //     <p>Conversion: {$subjectFunnel['overallConversionRate']}%</p>
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelStepsHTML)
        //     ."
        //     </ol>
        //     {$comparisonFunnelsHTML}
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
        //     <p>Conversion: {$subjectFunnel['overallConversionRate']}%</p>
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelStepsHTML)
        //     ."
        //     </ol>
        //     {$comparisonFunnelsHTML}
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
        //     <p>Conversion: {$subjectFunnel['overallConversionRate']}%</p>
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelStepsHTML)
        //     ."
        //     </ol>
        //     {$comparisonFunnelsHTML}
        // ";

        /**
         * V6.2
         */
        $messageContent = "
            Your task is to analyze and compare website conversion funnels. Below, I've provided data for my funnel and one or more comparison funnels. Calculate the conversion rate of each step in each funnel (conversion rate = previous step divided by subsequent step).

            Begin your analysis with, \"The biggest opportunity for improvement is…\" Limit your analysis to 40 words.

            I WANT TO KNOW WHICH TRANSITION (STEP TO STEP) IN MY FUNNEL HAS THE BIGGEST OPPORTUNITY FOR IMPROVEMENT COMPARED TO THE COMPARISON FUNNELS.
            THE SUBJECT FUNNEL STEP YOU IDENTIFY MUST HAVE A LOWER CONVERSION RATE THAN THE COMPARISONS AT THAT STEP.
            DON'T LIST COMPARISON FUNNEL STEPS WITH A LOWER CONVERSION RATE THAN THE SUBJECT FUNNEL STEP YOU IDENTIFY.
            IF THE SUBJECT FUNNEL IS PERFORMING BETTER THAN THE COMPARISON FUNNELS ON ALL OF ITS STEPS, INDICATE THAT IT IS PERFORMING BETTER THAN THE COMPARISONS.

            Now I will give you the data you need to complete the analysis:

            <h2>Funnel data</h2>

            <h3>Subject funnel: {$subjectFunnel['name']}</h3>
            
            <h4>Funnel steps:</h4>
            <ol>
            ".
                implode('', $subjectFunnelStepsHTML)
            ."
            </ol>
            {$comparisonFunnelsHTML}
        ";

        // /**
        //  * V6.3
        //  */
        // $messageContent = "
        //     Your task is to analyze and compare website conversion funnels. Below, I've provided data for my funnel and one or more comparison funnels.

        //     Begin your analysis with, \"The biggest opportunity for improvement is…\" Limit your analysis to 40 words.

        //     I WANT TO KNOW WHICH TRANSITION (STEP TO STEP) IN MY FUNNEL HAS THE BIGGEST OPPORTUNITY FOR IMPROVEMENT COMPARED TO THE COMPARISON FUNNELS.

        //     Now I will give you the data you need to complete the analysis:

        //     <h2>Funnel data</h2>

        //     <h3>Subject funnel: {$subjectFunnel['name']}</h3>
            
        //     <h4>Funnel steps:</h4>
        //     <ol>
        //     ".
        //         implode('', $subjectFunnelStepsHTML)
        //     ."
        //     </ol>
        //     {$comparisonFunnelsHTML}
        // ";

        // return $messageContent;

        $response = $this->GPTService->getResponse($messageContent);

        $content = $analysis->content .= $response;

        $analysis->update([
            'content' => $content,
        ]);

        return $analysis;
    }
}
