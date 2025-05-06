<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;

class Step2GetSubjectFunnelBOFI
{
    use AsAction;

    function handle(Analysis $analysis, $subjectFunnel, $comparisonFunnels)
    {
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
            'bofiPerformance' => null, // e.g. 6.13,
            'bofiConversionRate' => null, // e.g. 4.00,
            'bofiAssetChange' => null, // e.g. 1000,
        ];

        /**
         * Build an array with the ratio of each subject funnel step compared to corresponding steps in comparison funnels
         */
        foreach ($subjectFunnel['report']['steps'] as $index => $subjectFunnelStep) {
            // Skip the first step
            if ($index === 0) {
                continue;
            }

            // Get the conversion rate for this step in the subject funnel
            $subjectFunnelStepConversionRate = $subjectFunnelStep['conversionRate'];
            $reference['subjectFunnelSteps'][$index] = ['conversionRate' => $subjectFunnelStepConversionRate];

            // Get the conversion rates for this step in the comparison funnels
            foreach ($comparisonFunnels as $comparisonFunnel) {
                // Get the conversion rate for this step in the comparison funnel
                $comparisonFunnelStepConversionRate = $comparisonFunnel['report']['steps'][$index]['conversionRate'];

                // Add empty array to this part of the reference
                if (!isset($reference['subjectFunnelSteps'][$index]['comparisonConversionRates'])) {
                    $reference['subjectFunnelSteps'][$index]['comparisonConversionRates'] = [];
                }

                // Push to reference
                array_push($reference['subjectFunnelSteps'][$index]['comparisonConversionRates'], $comparisonFunnelStepConversionRate);
            }

            // Get the median of the comparison conversion rates
            if ($analysis->type === 'median') {
                $medianOfComparisonConversionRates = $this->calculateMedian($reference['subjectFunnelSteps'][$index]['comparisonConversionRates']);
            } else {
                $medianOfComparisonConversionRates = $this->findMax($reference['subjectFunnelSteps'][$index]['comparisonConversionRates']);
            }
            $reference['subjectFunnelSteps'][$index]['medianOfComparisons'] = $medianOfComparisonConversionRates;

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

            // Set step performance
            $subjectFunnelStepPerformance = $this->calculatePercentageChange($subjectFunnelStepConversionRate, $medianOfComparisonConversionRates);
            $reference['subjectFunnelSteps'][$index]['performance'] = $subjectFunnelStepPerformance;

            // Set step performance ratio against the median of comparisons
            $stepRatio = $medianOfComparisonConversionRates / $subjectFunnelStepConversionRate;
            $reference['subjectFunnelSteps'][$index]['ratio'] = $stepRatio;

            // Add the step ratio to the array
            array_push($reference['subjectFunnelStepRatios'], $stepRatio);
        }

        /**
         * Find the index of the largest ratio in the array
         * This is the BOFI step
         */
        if (!empty($reference['subjectFunnelStepRatios'])) {
            $largestRatio = max($reference['subjectFunnelStepRatios']); // Get the largest number in the array
            $indexOfLargestRatio = array_search($largestRatio, $reference['subjectFunnelStepRatios']); // Get the index of the largest number
            $reference['largestRatio'] = $largestRatio;
            $reference['bofiStepIndex'] = $indexOfLargestRatio;

            /** 
             * Get BOFI performance
             */
             // Setup the BOFI conversion rate
            $bofiConversionRate = $reference['subjectFunnelSteps'][$indexOfLargestRatio + 1]['conversionRate'];
            $reference['bofiConversionRate'] = $bofiConversionRate;

            // Setup the median of BOFI comparisons for the analysis
            $bofiMedianOfComparisons = $reference['subjectFunnelSteps'][$indexOfLargestRatio + 1]['medianOfComparisons'];

            // Get BOFI performance
            $bofiPerformance = $this->calculatePercentageChange($bofiConversionRate, $bofiMedianOfComparisons);
            $reference['bofiPerformance'] = $bofiPerformance;

            // $bofiAssetChange = ($subjectFunnel['report']['assets'] * $largestRatio) - $subjectFunnel['report']['assets'];
            // $reference['bofiAssetChange'] = $bofiAssetChange;

            /** 
             * Get BOFI performance
             */
            $lastStepIndex = count($subjectFunnel['report']['steps']) - 1;
            $lastStep = $subjectFunnel['report']['steps'][$lastStepIndex];

            if ($bofiPerformance >= 0) {
                // If the BOFI performance is zero (all steps perform same as comparisons), 
                // or if the BOFI performance is positive (BOFI performs better than comparisons), 
                // then the potential assets are zero.
                $reference['bofiAssetChange'] = 0;

            } elseif ($lastStepIndex !== $indexOfLargestRatio + 1 && $lastStep['users'] === 0) {
                // Last step is not converting users and is not the BOFI, no potential assets
                $reference['bofiAssetChange'] = 0;
        
            } elseif ($subjectFunnel['report']['assets'] != 0) {
                // Focus funnel is generating assets
                // Calculate the asset change from that number
                $bofiAssetChange = ($subjectFunnel['report']['assets'] * $largestRatio) - $subjectFunnel['report']['assets'];
                $reference['bofiAssetChange'] = $bofiAssetChange;

            } else {
                // Focus funnel is not generating assets
                // Calculate potential assets as if the BOFI step converted users at the same rate as the median of comparisons
                $users = $subjectFunnel['report']['steps'][$indexOfLargestRatio]['users']; // BOFI users
                $potentialUsers = $bofiMedianOfComparisons * $users;
                $assets = $potentialUsers * ($subjectFunnel['conversion_value'] / 100);
                $reference['bofiAssetChange'] = $assets / 100;
                
            }
        
            // Append to reference
            $appendedReference = $analysis->reference .= $this->generateReference($reference);

            // Update analysis
            $analysis->update([
                'subject_funnel_conversion_value' => $subjectFunnel->conversion_value,
                'bofi_step_index' => $reference['bofiStepIndex'],
                'bofi_performance' => $reference['bofiPerformance'],
                'bofi_conversion_rate' => $reference['bofiConversionRate'],
                'bofi_median_of_comparisons' => $bofiMedianOfComparisons,
                'bofi_asset_change' => $reference['bofiAssetChange'],
                'period' => '28 days',
                'reference' => $appendedReference,
            ]);
        } else {
            // Handle the case where there are no ratios
            // Set default values or log the issue
            $analysis->update([
                'subject_funnel_conversion_value' => $subjectFunnel->conversion_value,
                'period' => '28 days',
            ]);
        }

        return $analysis;
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
            $a += 0.001;
            $b += 0.001;

            // Calculate the percentage change
            $percentageChange = (($a - $b) / $b) * 100;

            // Round up to 2 decimal places
            $percentageChange = round($percentageChange);

            return $percentageChange;
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

    // TODO: Move this to a helper/service class
    function findMax($arrayOfNumbers) {
        return max($arrayOfNumbers);
    }

    function generateReference($reference) {
        $html = '';

        foreach ($reference['subjectFunnelSteps'] as $index => $subjectFunnelStep) {
            $count = $index;

            $html .= "<p><strong>Ratio for step {$count} of the Subject Funnel</strong></p>";

            $html .= "<p>Step {$count} conversion rate of Subject Funnel = {$subjectFunnelStep['conversionRate']}</p>";

            $html .= "<p>Step {$count} conversion rate of Comparison Funnels = " . implode(', ', $subjectFunnelStep['comparisonConversionRates']) . "</p>";

            $html .= "<p>Median of Comparisons = {$subjectFunnelStep['medianOfComparisons']}</p>";

            $html .= "<p>Ratio ({$subjectFunnelStep['conversionRate']} / {$subjectFunnelStep['medianOfComparisons']}) = {$subjectFunnelStep['ratio']}</p>";

            $html .= "<p>Subject funnel step performance (({$subjectFunnelStep['conversionRate']} - {$subjectFunnelStep['medianOfComparisons']}) / {$subjectFunnelStep['medianOfComparisons']}) * 100 = {$subjectFunnelStep['performance']}</p><br>";
        }

        $html .= "<p><strong>Subject Funnel step ratios:</strong> [" . implode(', ', $reference['subjectFunnelStepRatios']) . "]</p>";
        $html .= "<p><strong>Largest ratio:</strong> {$reference['largestRatio']}</p><br>";

        return $html;
    }
}
