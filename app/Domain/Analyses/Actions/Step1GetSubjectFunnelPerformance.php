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

        // dd([
        //     'subjectFunnelConversionRate' => $subjectFunnelConversionRate,
        //     'comparisonFunnelsConversionRates' => $comparisonFunnelsConversionRates,
        // ]);

        /**
         * Get the median of the comparison conversion rates
         */
        $medianOfComparisonConversionRates = $this->calculateMedian($comparisonFunnelsConversionRates);

        // dd($medianOfComparisonConversionRates);

        /**
         * Run percentage difference formula
         */
        // Calculate the absolute difference
        // $difference = abs($subjectFunnelConversionRate - $medianOfComparisonConversionRates);

        // // Calculate the average of the two numbers
        // $average = ($subjectFunnelConversionRate + $medianOfComparisonConversionRates) / 2;
        
        // // Calculate the percentage difference
        // $percentageDifference = ($difference / $average) * 100;

        $percentageDifference = $this->calculatePercentageDifference($subjectFunnelConversionRate, $medianOfComparisonConversionRates);

        if ($percentageDifference === INF) {
            return $analysis;
        }

        // dd($percentageDifference);

        /**
         * Get subject funnel conversion rate percentage difference higher/lower
         */
        // try {
            // $percentageDifference = ($subjectFunnelConversionRate - $medianOfComparisonConversionRates) / $medianOfComparisonConversionRates * 100;
        // } catch(DivisionByZeroError $e) {
        //     $percentageDifference = 0;
        // }

        // dd(round($percentageDifference, 2));

        /**
         * Format the percentage difference to include a + or - sign
         */
        // $formattedPercentageDifference = ($percentageDifference >= 0 ? '+' : '') . round($percentageDifference, 2) . ($percentageDifference >= 0 ? '% higher' : '% lower');

        // dd($formattedPercentageDifference);

        $roundedPercentageDifference = round($percentageDifference, 2);

        // Update dashboard
        $analysis->dashboard->update([
            'subject_funnel_performance' => $roundedPercentageDifference,
        ]);

        // Update analysis
        $analysis->update([
            'subject_funnel_performance' => $roundedPercentageDifference,
            // 'content' => '
            //     <p>' . $formattedPercentageDifference . ($formattedPercentageDifference <= 0 ? '% lower' : '% higher') . ' than comparisons</p>
            // ',
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

    // TODO: Move this to a helper/service class
    function calculatePercentageDifference($a, $b) {
        // Calculate the absolute difference
        $difference = $a - $b;

        // Calculate the average of the two numbers
        $average = ($a + $b) / 2;

        // Check if the average is zero to prevent division by zero
        if ($average == 0) {
            return INF; // Return infinity
        }

        // Calculate the percentage difference
        $percentageDifference = ($difference / $average) * 100;

        return $percentageDifference;
    }
}
