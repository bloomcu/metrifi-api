<?php

namespace DDD\Domain\Benchmarks\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use DDD\Domain\Benchmarks\Benchmark;
use DDD\Domain\Funnels\Funnel;

class CalculateBenchmarkAction
{
    use AsAction;

    function handle(Benchmark $benchmark)
    {
        if (!$benchmark->category_id) {
            throw new \Exception('Benchmark does not have a category.');
        }

        $funnels = Funnel::where('category_id', $benchmark->category_id)->get();

        if ($funnels->count() === 0) {
            throw new \Exception('No funnels found for this benchmark\'s category.');
        }

        $conversionRates = $funnels->map(function($funnel) {
            return $funnel->snapshots['last28Days']['conversionRate'];
        });

        $quartiles = $this->calculateBenchmark($conversionRates->toArray());

        $benchmark->update([
            'bottom' => $quartiles[0],
            'median' => $quartiles[1],
            'top' => $quartiles[2],
            'count' => $funnels->count(),
            'funnels' => $funnels->pluck('id')->toArray(),
        ]);

        return;
    }

    function calculateBenchmark($data) {
        $filteredData = $this->removeOutliers($data);
        return $this->calculateQuartiles($filteredData);
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

    function calculateQuartiles($data) {
        sort($data);
        $count = count($data);
        $median = $data[intval($count / 2)];
        $firstQuartile = $data[intval($count / 4)];
        $thirdQuartile = $data[intval(3 * $count / 4)];
        return [$firstQuartile, $median, $thirdQuartile];
    }
}
