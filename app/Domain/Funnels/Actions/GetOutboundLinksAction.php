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
        $report = GoogleAnalyticsData::fetchOutboundClicks(
            connection: $funnel->connection, 
            startDate: '28daysAgo',
            endDate: 'today',
        );

        // Get the measurable for the last step of the funnel
        // TODO: This is a temporary solution. We need to refactor this to get the terminal page path from the funnel
        $lastStep = $funnel->steps()->latest()->first();
        $terminalPagePath = $lastStep->measurables[0]['measurable'];

        return $this->findOutboundLinks($report['rows'], $terminalPagePath);
    }

    private function findOutboundLinks($rows, $pagePath) {
        $links = [];

        foreach ($rows as $row) {
            // Dimension values include the link URL, link domain, and page path for each row.
            $dimensionValues = isset($row['dimensionValues']) ? $row['dimensionValues'] : [];

            if (count($dimensionValues) == 3) {
                // The third item in "dimensionValues" represents the page path
                if (isset($dimensionValues[2]['value']) && $dimensionValues[2]['value'] == $pagePath) {
                    // The first item in "dimensionValues" represents the link URL
                    $links[] = isset($dimensionValues[0]['value']) ? $dimensionValues[0]['value'] : '';
                }
            }
        }

        return $links;
    }
}