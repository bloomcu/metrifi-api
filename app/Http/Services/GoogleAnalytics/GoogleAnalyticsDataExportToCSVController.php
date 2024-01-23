<?php
namespace DDD\Http\Services\GoogleAnalytics;

use Illuminate\Http\Request;
use DDD\Domain\Connections\Connection;
use DDD\App\Facades\GoogleAnalytics\GoogleAnalyticsData;
use DDD\App\Controllers\Controller;

class GoogleAnalyticsDataExportToCSVController extends Controller
{
    public function exportReport(Connection $connection, Request $request)
    {   
        // Get report from Google Analytics
        $report = GoogleAnalyticsData::runReport($connection, $request);

        // Setup filename
        $fileName = $connection->name . '-report.csv';

        // Setup file headers
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        // Setup CSV columns
        $columns = [];
        foreach ($report['dimensionHeaders'] as $dimensionHeader) {
            array_push($columns, $dimensionHeader['name']);
        }
        foreach ($report['metricHeaders'] as $metricHeader) {
            array_push($columns, $metricHeader['name']);
        }

        // Setup CSV rows
        $callback = function() use($report, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Iterate report rows
            foreach ($report['rows'] as $row) {
                foreach ($row['dimensionValues'] as $dimension) {
                    fputcsv($file, array(
                        str_replace(chr(194), '', $dimension['value']),
                    ));
                }

                foreach ($row['metricValues'] as $metric) {
                    fputcsv($file, array(
                        str_replace(chr(194), '', $metric['value']),
                    ));
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
