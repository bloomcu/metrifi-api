<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class GenerateFunnelEndpointsAction
{
    use AsAction;

    function handle(Connection $connection, string $startingPagePath)
    {   
        $report = $this->getReport($connection);

        return $this->findFunnelEndpoints($report, $startingPagePath);
    }

    private function findFunnelEndpoints($report, $startingPagePath) {
        
        // Filter report to get paths that start with the starting page path
        $allPaths = array_filter($report['rows'], function($row) use ($startingPagePath) {
            return strpos($row['dimensionValues'][0]['value'], $startingPagePath) === 0;
        });
    
        // Map to get just the path values
        $allPaths = array_map(function($row) {
            return $row['dimensionValues'][0]['value'];
        }, $allPaths);

        // Filter paths to include only items that start and end with "/"
        $allPaths = array_filter($allPaths, function($path) {
            return strncmp($path, "/", 1) === 0;
        });
    
        // Identify terminal page paths
        $terminalPaths = [];
        foreach ($allPaths as $path) {
            $isTerminal = true;
            foreach ($allPaths as $otherPath) {
                if (strpos($otherPath, $path) === 0 && $otherPath != $path) {
                    $isTerminal = false;
                    break;
                }
            }
            if ($isTerminal) {
                $terminalPaths[] = $path;
            }
        }
    
        return $terminalPaths;
    }

    private function getReport(Connection $connection)
    {
        return GoogleAnalyticsData::pageUsers(
            connection: $connection, 
            startDate: '28daysAgo',
            endDate: 'today',
            pagePaths: null,
        );
    }
}