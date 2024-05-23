<?php

namespace DDD\Domain\Benchmarks\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Benchmarks\Benchmark;

class CalculateBenchmarkAction
{
    use AsAction;

    function handle(Benchmark $benchmark, array $data)
    {
        $filteredData = $this->removeOutliers($data);
        return $this->calculateQuartiles($filteredData);
    }

    function calculateQuartiles($data) {
        sort($data);
        $count = count($data);
        $median = $data[intval($count / 2)];
        $firstQuartile = $data[intval($count / 4)];
        $thirdQuartile = $data[intval(3 * $count / 4)];
        return [$firstQuartile, $median, $thirdQuartile];
    }
    
    function removeOutliers($data) {
        $quartiles = $this->calculateQuartiles($data);
        $iqr = $quartiles[2] - $quartiles[0];
        $lowerBound = $quartiles[0] - 1.5 * $iqr;
        $upperBound = $quartiles[2] + 1.5 * $iqr;
        return array_filter($data, function($value) use ($lowerBound, $upperBound) {
            return ($value >= $lowerBound && $value <= $upperBound);
        });
    }
}
