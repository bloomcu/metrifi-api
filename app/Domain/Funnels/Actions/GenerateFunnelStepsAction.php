<?php

namespace DDD\Domain\Funnels\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;

class GenerateFunnelStepsAction
{
    use AsAction;

    function handle(Funnel $funnel, string $terminalPagePath)
    {   
        // Break terminal page path into segments
        $pagePaths = $this->segmentTerminalPagePath($terminalPagePath);
        
        // Validate the segments as having traffic
        $validPagePaths = $this->validatePagePaths($funnel, $pagePaths);

        // Create funnel steps.
        $steps = [];
        foreach ($validPagePaths as $key => $pagePath) {
            $step = $funnel->steps()->create([
                'order' => $key + 1,
                'name' => $pagePath,
                'measurables' => [
                    [
                        'metric' => 'pageUsers',
                        'pagePath' => $pagePath,
                    ]
                ]
            ]);

            array_push($steps, $step);
        }

        return $steps;
    }

    private function segmentTerminalPagePath(string $terminalPagePath) {
        // Break the path into all its path segments
        $segments = explode('/', $terminalPagePath);

        // Ensure segments array does not contain empty strings at the start and end
        $segments = array_filter($segments, function($segment) {
            return !empty($segment);
        });

        // Collect the segments, root is included by default
        $paths = ["/"];

        // For each segment, add the segment to the paths array
        foreach ($segments as $i => $segment) {
            $pathSegment = implode('/', array_slice($segments, 0, $i));
            $paths[] = "/" . $pathSegment . "/";
        }

        return $paths;
    }

    private function validatePagePaths(Funnel $funnel, array $pagePaths) {
        $report = GoogleAnalyticsData::pageUsers(
            connection: $funnel->connection, 
            startDate: '28daysAgo',
            endDate: 'today',
            pagePaths: null,
        );

        // Filter out paths not present in GA pageUsers report
        $validPaths = [];
        foreach ($report['rows'] as $row) {
            if (isset($row['dimensionValues']) && in_array($row['dimensionValues'][0]['value'], $pagePaths)) {
                $validPaths[] = $row['dimensionValues'][0]['value'];
            }
        }

        // Sort array by length of path
        usort($validPaths, function($a, $b) {
            return strlen($a) - strlen($b);
        });

        return $validPaths;
    }
}