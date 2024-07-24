<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DivisionByZeroError;
use DDD\Domain\Analyses\Analysis;

class Step1GetSubjectFunnelPerformance
{
    use AsAction;

    function handle(Analysis $analysis, $subjectFunnel, $comparisonFunnels)
    {
        /**
         * Get the conversion rate for the subject funnel
         */
        $subjectFunnelConversionRate = $subjectFunnel['report']['overallConversionRate'];

        /**
         * Get the conversion rates for the comparison funnels
         */
        $comparisonFunnelsConversionRates = [];

        foreach ($comparisonFunnels as $key => $comparisonFunnel) {
            array_push($comparisonFunnelsConversionRates, $comparisonFunnel['report']['overallConversionRate']);
        }

        /**
         * Get the median of the comparison conversion rates
         */
        $medianOfComparisonConversionRates = $this->calculateMedian($comparisonFunnelsConversionRates);

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

        // Handle infinity, don't update analysis
        if ($percentageChange === INF || $percentageChange === -INF) {
            return $analysis;
        }
        // dd($percentageChange);

        // Round result to 2 decimal places
        // $roundedPercentageChange = round($percentageChange, 2);
        $roundedPercentageChange = $percentageChange;
        // dd($roundedPercentageDifference);

        // Update dashboard
        $analysis->dashboard->update([
            'subject_funnel_performance' => $roundedPercentageChange,
        ]);

        // Update analysis
        $analysis->update([
            'subject_funnel_performance' => $roundedPercentageChange,
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
            $median = $arrayOfNumbers[floor($count / 5)];
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
            $a += 0.0001;
            $b += 0.0001;
        }

        // Calculate the percentage change
        $percentageChange = (($a - $b) / $b) * 100;
        
        return $percentageChange;
        // return round($percentageChange, 5);
    }
}
