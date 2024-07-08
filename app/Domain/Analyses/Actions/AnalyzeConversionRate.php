<?php

namespace DDD\Domain\Analyses\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Analyses\Analysis;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class AnalyzeConversionRate
{
    use AsAction;

    function handle(Analysis $analysis, string $period = 'last28Days')
    {
        // Bail early if dashboard has no funnels
        if (count($analysis->dashboard->funnels) === 0) {
            throw new \Exception('Dashboard has no funnels.');
        }

        // Bail early if subject funnel has no steps
        if (count($analysis->subjectFunnel->steps) === 0) {
            throw new \Exception('Subject funnel has no steps.');
        }

        $p = match ($period) {
            'yesterday' => [
                'startDate' => now()->subDays(1)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last7Days' => [
                'startDate' => now()->subDays(7)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ],
            'last28Days' => [
                'startDate' => now()->subDays(28)->format('Y-m-d'),
                'endDate' => now()->subDays(1)->format('Y-m-d'),
            ]
        };

        /**
         * Get the conversion rate for the subject funnel
         */
        $subjectFunnelConversionRate = '';

        $subjectFunnelReport = GoogleAnalyticsData::funnelReport(
            connection: $analysis->subjectFunnel->connection, 
            startDate: $p['startDate'], 
            endDate: $p['endDate'],
            steps: $analysis->subjectFunnel->steps->toArray(),
        );

        $subjectFunnelConversionRate = $subjectFunnelReport['overallConversionRate'];

        /**
         * Get the conversion rates for the comparison funnels
         */
        $comparisonFunnelsConversionRates = [];

        foreach ($analysis->dashboard->funnels as $key => $funnel) {
            if ($key === 0) continue; // Skip subject funnel (already processed above)

            $report = GoogleAnalyticsData::funnelReport(
                connection: $funnel->connection, 
                startDate: $p['startDate'], 
                endDate: $p['endDate'],
                steps: $funnel->steps->toArray(),
            );

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

        /**
         * Format the percentage difference to include a + or - sign
         */
        $formattedPercentageDifference = ($percentageDifference >= 0 ? '+' : '') . number_format($percentageDifference, 2) . ($percentageDifference >= 0 ? '% higher' : '% lower');
        
        $analysis->update([
            'content' => '<p><strong>Conversion rate:</strong><br>' . $formattedPercentageDifference . ' than comparisons</p>',
        ]);

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
