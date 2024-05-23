<?php

namespace DDD\Http\Benchmarks;

use DDD\Domain\Benchmarks\Resources\BenchmarkResource;
use DDD\Domain\Benchmarks\Benchmark;
use DDD\Domain\Benchmarks\Actions\CalculateBenchmarkAction;
use DDD\App\Controllers\Controller;

class BenchmarkCalculateController extends Controller
{
    public function calculate(Benchmark $benchmark)
    {
        CalculateBenchmarkAction::run($benchmark);

        return new BenchmarkResource($benchmark);
    }
}
