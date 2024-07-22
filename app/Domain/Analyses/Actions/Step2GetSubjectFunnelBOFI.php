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

        $meta = '';
        
        $reference = [
            'subjectFunnelSteps' => [
                // e.g., 'conversionRate' => 6.13,
                // e.g., 'comparisonConversionRates' => [9.92, 15.38],
                // e.g., 'medianOfComparisons' => 12.65,
                // e.g., 'ratio' => 2.0636215334421,
                // e.g., 'performance' => -51.541501976285,
            ],
            'subjectFunnelStepRatios' => [
                // e.g., 2.0636215334421, 0.94232943837165
            ],
            'largestRatio' => null, // e.g. 2.0636215334421
            'bofiStepIndex' => null, // e.g. 1
            // 'bofiStepName' => '', // e.g. 'Auto Loan page',
            'bofiPerformance' => null, // e.g. 6.13,
            'bofiAssetChange' => null, // e.g. 1000,
        ];

        /**
         * Build an array with the ratio of each subject funnel step compared to corresponding steps in comparison funnels
         */
        $subjectFunnelStepRatios = [];
        
        foreach ($subjectFunnel['report']['steps'] as $index => $subjectFunnelStep) {
            if ($index === 0) {
                continue;
            }
            
            $count = $index;

            // $meta .= "<p><strong>Ratio for step {$count} of the Subject Funnel</strong></p>";

            // Get the conversion rate for this step in the subject funnel
            $subjectFunnelStepConversionRate = $subjectFunnelStep['conversionRate'];

            $reference['subjectFunnelSteps'][$index] = [
                'conversionRate' => $subjectFunnelStepConversionRate,
            ];

            // $meta .= "<p>Step {$count} conversion rate of Subject Funnel = {$subjectFunnelStepConversionRate}</p>";

            // Get the conversion rates for this step in the comparison funnels
            $comparisonConversionRates = [];
            foreach ($comparisonFunnels as $comparisonFunnel) {
                // Get the conversion rate for this step in the comparison funnel
                $comparisonFunnelStepConversionRate = $comparisonFunnel['report']['steps'][$index]['conversionRate'];

                // Add empty array to this part of the reference
                if (!isset($reference['subjectFunnelSteps'][$index]['comparisonConversionRates'])) {
                    $reference['subjectFunnelSteps'][$index]['comparisonConversionRates'] = [];
                }

                // Push to reference
                array_push($reference['subjectFunnelSteps'][$index]['comparisonConversionRates'], $comparisonFunnelStepConversionRate);

                // $meta .= "<p>Step {$count} conversion rate of Comparison Funnel = {$comparisonFunnelStepConversionRate}</p>";
            }

            // Get the median of the comparison conversion rates
            $medianOfComparisonConversionRates = $this->calculateMedian($reference['subjectFunnelSteps'][$index]['comparisonConversionRates']);

            $reference['subjectFunnelSteps'][$index]['medianOfComparisons'] = $medianOfComparisonConversionRates;
            // $meta .= "<p>Median of Comparisons = {$medianOfComparisonConversionRates}</p>";

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

            $subjectFunnelStepPerformance = $this->calculatePercentageChange($subjectFunnelStepConversionRate, $medianOfComparisonConversionRates);

            $reference['subjectFunnelSteps'][$index]['performance'] = $subjectFunnelStepPerformance;

            // dd($subjectFunnelStepConversionRate, $medianOfComparisonConversionRates);

            $stepRatio = $medianOfComparisonConversionRates / $subjectFunnelStepConversionRate;
            // $stepRatio = round($stepRatio, 2);

            $reference['subjectFunnelSteps'][$index]['ratio'] = $stepRatio;
            // $meta .= "<p>Ratio ({$subjectFunnelStepConversionRate} / {$medianOfComparisonConversionRates}) = {$stepRatio}</p>";

            
            // $meta .= "<p>Subject funnel step performance (({$subjectFunnelStepConversionRate} - {$medianOfComparisonConversionRates}) / {$medianOfComparisonConversionRates}) * 100 = {$subjectFunnelStepPerformance}</p><br>";

            // Add the step ratio to the array
            
            array_push($reference['subjectFunnelStepRatios'], $stepRatio);
        }

        // $meta .= "<p><strong>Subject Funnel Step Ratios:</strong> [" . implode(', ', $subjectFunnelStepRatios) . "]</p>";

        /**
         * Find the index of the largest ratio in the array
         */
        $largestRatio = max($reference['subjectFunnelStepRatios']); // Get the largest number in the array
        $indexOfLargestRatio = array_search($largestRatio, $reference['subjectFunnelStepRatios']); // Get the index of the largest number

        $reference['largestRatio'] = $largestRatio;
        // $meta .= "<p><strong>Largest ratio:</strong> {$largestRatio}</p>";

        /**
         * Find the subject funnel BOFI step by index
         */
        // $subjectFunnelBOFIStep = $subjectFunnel['report']['steps'][$indexOfLargestRatio - 1];
        // $subjectFunnelBOFIStep = $subjectFunnel['report']['steps'][$indexOfLargestRatio];

        // $content = "The biggest opportunity for improvement is step " . $indexOfLargestRatio + 1 .": {$subjectFunnelBOFIStep['name']} (" . $subjectFunnel['report']['steps'][$indexOfLargestRatio + 1]['conversionRate'] . "%)\n\n";

        // dd($meta);
        // dd($subjectFunnelStepRatios);

        $reference['bofiStepIndex'] = $indexOfLargestRatio;

        // $reference['bofiStepName'] = $subjectFunnel['report']['steps'][$indexOfLargestRatio]['name'];

        /** 
         * Get bofi performance
         * Use small constant strategy against division by zero issues 
         * 
         * Cache the bofi conversion rate and median of comparisons
         * Check for division by zero and add a small constant. To avoid division by zero or getting a zero ratio, you could add a 
         * small constant (like 0.01) to both the numerator and the denominator. This technique is sometimes used in data analysis to handle zero values.
         */
        $bofiConversionRate = $reference['subjectFunnelSteps'][$indexOfLargestRatio + 1]['conversionRate'];
        $bofiMedianOfComparisons = $reference['subjectFunnelSteps'][$indexOfLargestRatio + 1]['medianOfComparisons'];
        // if ($bofiConversionRate === 0 || $bofiMedianOfComparisons === 0) {
        //     $bofiConversionRate += 0.01;
        //     $bofiMedianOfComparisons += 0.01;
        // }
        $reference['bofiPerformance'] = $this->calculatePercentageChange($bofiConversionRate, $bofiMedianOfComparisons);

        $reference['bofiAssetChange'] = ($subjectFunnel['report']['assets'] * $largestRatio) - $subjectFunnel['report']['assets'];

        // Update analysis
        $analysis->update([
            'bofi_step_index' => $reference['bofiStepIndex'],
            // 'bofi_step_name' => $reference['bofiStepName'],
            'bofi_performance' => $reference['bofiPerformance'],
            'bofi_asset_change' => $reference['bofiAssetChange'],
            'period' => '28 days',
            'content' => $reference,
            'meta' => $this->generateMeta($reference),
        ]);

        return $analysis;
    }

    function generateMeta($reference) {
        $meta = '';

        foreach ($reference['subjectFunnelSteps'] as $index => $subjectFunnelStep) {
            $count = $index;

            $meta .= "<p><strong>Ratio for step {$count} of the Subject Funnel</strong></p>";

            $meta .= "<p>Step {$count} conversion rate of Subject Funnel = {$subjectFunnelStep['conversionRate']}</p>";

            $meta .= "<p>Step {$count} conversion rate of Comparison Funnel = " . implode(', ', $subjectFunnelStep['comparisonConversionRates']) . "</p>";

            $meta .= "<p>Median of Comparisons = {$subjectFunnelStep['medianOfComparisons']}</p>";

            $meta .= "<p>Ratio ({$subjectFunnelStep['conversionRate']} / {$subjectFunnelStep['medianOfComparisons']}) = {$subjectFunnelStep['ratio']}</p>";

            $meta .= "<p>Subject funnel step performance (({$subjectFunnelStep['conversionRate']} - {$subjectFunnelStep['medianOfComparisons']}) / {$subjectFunnelStep['medianOfComparisons']}) * 100 = {$subjectFunnelStep['performance']}</p><br>";
        }

        $meta .= "<p><strong>Subject Funnel Step Ratios:</strong> [" . implode(', ', $reference['subjectFunnelStepRatios']) . "]</p>";

        $meta .= "<p><strong>Largest ratio:</strong> {$reference['largestRatio']}</p>";

        return $meta;
    }

    // TODO: Move this to a helper/service class
    function calculatePercentageChange($a, $b) {
        /** 
         * Use small constant strategy against division by zero issues 
         * 
         * Check for division by zero and add a small constant. To avoid division by zero or getting a zero ratio, you could add a 
         * small constant (like 0.01) to both the numerator and the denominator. This technique is sometimes used in data analysis to handle zero values.
         */
        if ($a == 0 || $b == 0) {
            $a += 0.0001;
            $b += 0.0001;
        }

        // Calculate the percentage change
        $percentageChange = (($a - $b) / $b) * 100;
        
        return $percentageChange;
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
