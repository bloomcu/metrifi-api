<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;

class Step2GetSubjectFunnelBOFI
{
    use AsAction;

    function handle(Analysis $analysis, $subjectFunnel, $comparisonFunnels)
    {
        // dd($subjectFunnel);
        
        $meta = "";

        /**
         * Build an array with the ratio of each subject funnel step compared to corresponding steps in comparison funnels
         */
        $subjectFunnelStepRatios = [];
        
        foreach ($subjectFunnel['report']['steps'] as $index => $subjectFunnelStep) {
            if ($index === 0) {
                continue;
            }
            
            $count = $index;

            $meta .= "<p><strong>Ratio for step {$count} of the Subject Funnel</strong></p>";

            // Get the conversion rate for this step in the subject funnel
            $subjectFunnelStepConversionRate = $subjectFunnelStep['conversionRate'];

            $meta .= "<p>Step {$count} conversion rate of Subject Funnel = {$subjectFunnelStepConversionRate}</p>";

            // Get the conversion rates for this step in the comparison funnels
            $comparisonConversionRates = [];
            foreach ($comparisonFunnels as $comparisonFunnel) {
                $comparisonFunnelStepConversionRate = $comparisonFunnel['report']['steps'][$index]['conversionRate'];
                array_push($comparisonConversionRates, $comparisonFunnelStepConversionRate);

                $meta .= "<p>Step {$count} conversion rate of Comparison Funnel = {$comparisonFunnelStepConversionRate}</p>";
            }

            // Get the median of the comparison conversion rates
            $medianOfComparisonConversionRates = $this->calculateMedian($comparisonConversionRates);

            $meta .= "<p>Median of Comparisons = {$medianOfComparisonConversionRates}</p>";

            /** 
             * Use small constant strategy against division by zero issues 
             * 
             * Get the ratio of the subject funnel step conversion rate to the median of the comparison conversion rates
             * Check for division by zero and add a small constant. To avoid division by zero or getting a zero ratio, you could add a 
             * small constant (like 0.01) to both the numerator and the denominator. This technique is sometimes used in data analysis to handle zero values.
             */
            if ($subjectFunnelStepConversionRate === 0 || $medianOfComparisonConversionRates === 0) {
                $subjectFunnelStepConversionRate += 0.01;
                $medianOfComparisonConversionRates += 0.01;
            }

            // dd($subjectFunnelStepConversionRate, $medianOfComparisonConversionRates);

            $stepRatio = $medianOfComparisonConversionRates / $subjectFunnelStepConversionRate;
            // $stepRatio = round($stepRatio, 2);

            $meta .= "<p>Ratio ({$subjectFunnelStepConversionRate} / {$medianOfComparisonConversionRates}) = {$stepRatio}</p><br>";

            // Add the step ratio to the array
            array_push($subjectFunnelStepRatios, $stepRatio);
        }

        $meta .= "<p><strong>Subject Funnel Step Ratios:</strong> [" . implode(', ', $subjectFunnelStepRatios) . "]</p>";

        /**
         * Find the index of the largest ratio in the array
         */
        $largestRatio = max($subjectFunnelStepRatios); // Get the largest number in the array
        $indexOfLargestRatio = array_search($largestRatio, $subjectFunnelStepRatios); // Get the index of the largest number

        $meta .= "<p><strong>Largest ratio:</strong> {$largestRatio}</p>";

        /**
         * Find the subject funnel BOFI step by index
         */
        // $subjectFunnelBOFIStep = $subjectFunnel['report']['steps'][$indexOfLargestRatio - 1];
        $subjectFunnelBOFIStep = $subjectFunnel['report']['steps'][$indexOfLargestRatio];

        $content = "The biggest opportunity for improvement is step " . $indexOfLargestRatio + 1 .": {$subjectFunnelBOFIStep['name']} (" . $subjectFunnel['report']['steps'][$indexOfLargestRatio + 1]['conversionRate'] . "%)\n\n";

        // dd($meta);
        // dd($subjectFunnelStepRatios);

        // Update analysis
        $analysis->update([
            'content' => $content,
            'meta' => $meta,
        ]);

        return $analysis;
    }

    // TODO: Move this to a helper/service class
    function calculateMedian($arrayOfNumbers) {
        sort($arrayOfNumbers);
        $count = count($arrayOfNumbers);
        
        if ($count % 2 == 0) {
            // If the number of elements is even
            $middle1 = $arrayOfNumbers[$count / 2 - 1];
            $middle2 = $arrayOfNumbers[$count / 2];
            $median = ($middle1 + $middle2) / 2;
        } else {
            // If the number of elements is odd
            $median = $arrayOfNumbers[floor($count / 2)];
        }
        
        return $median;
    }
}
