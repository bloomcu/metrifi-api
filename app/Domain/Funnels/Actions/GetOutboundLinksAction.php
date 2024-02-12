<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Pages\Page;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class GetOutboundLinksAction
{
    use AsAction;

    /**
     * @param  Page  $page
     * @return string
     */
    function handle(Funnel $funnel)
    {   
        if (!$funnel->steps()->exists()) {
            throw new \Exception('No steps found for funnel');
        }
        
        // Get the measurable for the last step of the funnel
        // TODO: Consider refactoring this to use a terminal page path from a funnel column
        $max = $funnel->steps()->max('order');
        $lastStep = $funnel->steps()->where('order', $max)->first();
        $terminalPagePath = $lastStep->measurables[0]['measurable'];

        // Get outbound clicks from GA
        $report = GoogleAnalyticsData::fetchOutboundClicks(
            connection: $funnel->connection, 
            startDate: '28daysAgo',
            endDate: 'today',
        );

        // Find outbound links that were clicked on the terminal page path page
        $links = [];
        foreach ($report['rows'] as $row) {
            // Dimension values include the link URL, link domain, and page path for each row.
            $dimensionValues = isset($row['dimensionValues']) ? $row['dimensionValues'] : [];

            if (count($dimensionValues) == 3) {
                // The third item in "dimensionValues" represents the page path
                if (isset($dimensionValues[2]['value']) && $dimensionValues[2]['value'] == $terminalPagePath) {
                    // The first item in "dimensionValues" represents the link URL
                    $links[] = isset($dimensionValues[0]['value']) ? $dimensionValues[0]['value'] : '';
                }
            }
        }

        return $links;
    }
}