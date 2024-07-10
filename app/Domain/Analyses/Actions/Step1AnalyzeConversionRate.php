<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;

class Step1AnalyzeConversionRate
{
    use AsAction;

    function handle(Analysis $analysis, $subjectFunnelReport, $comparisonFunnelReports)
    {
        /**
         * Get the conversion rate for the subject funnel
         */
        $subjectFunnelConversionRate = $subjectFunnelReport['overallConversionRate'];

        /**
         * Get the conversion rates for the comparison funnels
         */
        $comparisonFunnelsConversionRates = [];

        foreach ($comparisonFunnelReports as $key => $report) {
            array_push($comparisonFunnelsConversionRates, $report['overallConversionRate']);
        }

        /**
         * Get the median of the comparison conversion rates
         */
        $medianOfComparisonConversionRates = $this->calculateMedian($comparisonFunnelsConversionRates);

        /**
         * Get subject funnel conversion rate percentage higher/lower
         */
        $percentageDifference = ($subjectFunnelConversionRate - $medianOfComparisonConversionRates) / $medianOfComparisonConversionRates * 100;

        // Update dashboard
        $analysis->dashboard->update([
            'subject_funnel_performance' => number_format($percentageDifference, 2),
        ]);

        /**
         * Format the percentage difference to include a + or - sign
         */
        // $formattedPercentageDifference = ($percentageDifference >= 0 ? '+' : '') . number_format($percentageDifference, 2) . ($percentageDifference >= 0 ? '% higher' : '% lower');
        
        // Update analysis
        // $analysis->update([
        //     'content' => '<h3>Subject funnel conversion rate:</h3><p>' . $formattedPercentageDifference . ' than comparisons</p>',
        // ]);

        return $analysis;
    }

    function calculateMedian($data) {
        sort($data); // Step 1: Sort the array
        $count = count($data);
        
        if ($count % 2 == 0) {
            // If the number of elements is even
            $middle1 = $data[$count / 2 - 1];
            $middle2 = $data[$count / 2];
            $median = ($middle1 + $middle2) / 2;
        } else {
            // If the number of elements is odd
            $median = $data[floor($count / 2)];
        }
        
        return $median;
    }
}
