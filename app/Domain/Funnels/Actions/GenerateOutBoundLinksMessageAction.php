<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class GenerateOutBoundLinksMessageAction
{
    use AsAction;

    function handle(Funnel $funnel)
    {   
        // TODO: Use the method in our GoogleAnalyticsDataService class for this
        
        if (!$funnel->steps()->exists()) {
            throw new \Exception('No steps found for funnel');
        }

        $report = $this->getOutboundLinksReport($funnel->connection);
        $terminalPagePath = $this->getFunnelLastStepTerminalPagePath($funnel);
        $links = $this->getOutboundLinksByPagePath($report, $terminalPagePath);

        if ($links) {
            return $funnel->messages()->create([
                'type' => 'info',
                'title' => count($links) . ' outbound link(s) found for the final step of the funnel.',
                'json' => [
                    'links' => $links,
                    'pagePath' => $terminalPagePath,
                ],
            ]);
        }

        return null;
    }

    private function getOutboundLinksReport(Connection $connection) {
        return GoogleAnalyticsData::outboundLinkUsers(
            connection: $connection, 
            startDate: '28daysAgo',
            endDate: 'today',
        );
    }

    private function getFunnelLastStepTerminalPagePath(Funnel $funnel) {
        $max = $funnel->steps()->max('order');
        $lastStep = $funnel->steps()->where('order', $max)->first();
        $lastStepPath = $lastStep->measurables[0]['pagePath'];

        return $lastStepPath;
    }

    private function getOutboundLinksByPagePath(array $report, string $terminalPagePath) {
        // Find outbound links that were clicked on the terminal page path page
        $links = [];

        if (!isset($report['rows'])) {
            return $links;
        }
        
        foreach ($report['rows'] as $row) {
            // Dimension values include the link URL, link domain, and page path for each row.
            $dimensionValues = isset($row['dimensionValues']) ? $row['dimensionValues'] : [];

            if (count($dimensionValues) == 2) {
                // The third item in "dimensionValues" represents the page path
                if (isset($dimensionValues[1]['value']) && $dimensionValues[1]['value'] == $terminalPagePath) {
                    // The first item in "dimensionValues" represents the link URL
                    $links[] = isset($dimensionValues[0]['value']) ? $dimensionValues[0]['value'] : '';
                }
            }
        }

        return $links;
    }
}