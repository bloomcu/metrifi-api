<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;

class Step1GetSubjectFunnelPerformance
{
    use AsAction;

    function handle(Analysis $analysis, $subjectFunnel, $comparisonFunnels)
    {
        $reference = [
            'subjectFunnelUsers' => null, // 1000
            'subjectFunnelConversionRate' => null, // e.g. 4.29
            'comparisonFunnelsConversionRates' => [],  // e.g., 0.00, 6.09, 20.00
            'medianOfComparisonConversionRates' => null, // e.g. 2.0636215334421
            'percentageChange' => null, // e.g. 29.56
        ];

        /**
         * Get the number of users at the top of the subject funnel
         */
        $subjectFunnelUsers = $subjectFunnel['report']['steps'][0]['users'];
        $reference['subjectFunnelUsers'] = $subjectFunnelUsers;

        /**
         * Get the conversion rate for the subject funnel
         */
        $subjectFunnelConversionRate = $subjectFunnel['report']['overallConversionRate'];
        $reference['subjectFunnelConversionRate'] = $subjectFunnelConversionRate;

        /**
         * Get the conversion rates for the comparison funnels
         */
        $comparisonFunnelsConversionRates = [];
        foreach ($comparisonFunnels as $key => $comparisonFunnel) {
            array_push($comparisonFunnelsConversionRates, $comparisonFunnel['report']['overallConversionRate']);
        }
        $reference['comparisonFunnelsConversionRates'] = $comparisonFunnelsConversionRates;

        /**
         * Get the median of the comparison conversion rates
         */
        $medianOfComparisonConversionRates = $this->calculateMedian($comparisonFunnelsConversionRates);
        $reference['medianOfComparisonConversionRates'] = $medianOfComparisonConversionRates;

        // dd([
        //     'subjectFunnelConversionRate' => $subjectFunnelConversionRate,
        //     'medianOfComparisonConversionRates' => $medianOfComparisonConversionRates,
        //     '($medianOfComparisonConversionRates - $subjectFunnelConversionRate)' => $medianOfComparisonConversionRates - $subjectFunnelConversionRate,
        // ]);

        // Test figures
        // $subjectFunnelConversionRate = 7.33;
        // $medianOfComparisonConversionRates = 0.07;
        
        /**
         * Get subject funnel conversion rate percentage difference higher/lower
         */
        $percentageChange = $this->calculatePercentageChange($subjectFunnelConversionRate, $medianOfComparisonConversionRates);
        $reference['percentageChange'] = $percentageChange;

        // Handle infinity, don't update analysis
        // if ($percentageChange === INF || $percentageChange === -INF) {
        //     return $analysis;
        // }
        // dd($percentageChange);

        // Round result to 2 decimal places
        // $roundedPercentageChange = round($percentageChange, 2);
        // $roundedPercentageChange = $percentageChange;
        // dd($roundedPercentageDifference);

        // Update dashboard
        // $analysis->dashboard->update([
        //     'subject_funnel_performance' => $percentageChange,
        // ]);

        // Update analysis
        $analysis->update([
            'subject_funnel_users' => $subjectFunnelUsers,
            'subject_funnel_performance' => $percentageChange,
            'subject_funnel_conversion_rate' => $subjectFunnelConversionRate,
            'median_of_comparison_conversion_rates' => $medianOfComparisonConversionRates,
            'reference' => $this->generateReference($reference),
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
        // return round($median, 5);
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
        // return round($percentageChange, 5);
    }

    function generateReference($reference) {
        $html = '';

        $html .= "<p><strong>Subject Funnel users:</strong> {$reference['subjectFunnelUsers']}</p>";
        $html .= "<p><strong>Subject Funnel conversion rate:</strong> {$reference['subjectFunnelConversionRate']}</p>";
        $html .= "<p><strong>Median of comparison conversion rates:</strong> {$reference['medianOfComparisonConversionRates']}</p><br>";

        return $html;
    }
}
