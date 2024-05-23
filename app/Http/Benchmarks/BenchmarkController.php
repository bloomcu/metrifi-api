<?php

namespace DDD\Http\Benchmarks;

use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Requests\FunnelUpdateRequest;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Benchmarks\Resources\BenchmarkResource;
use DDD\Domain\Benchmarks\Requests\BenchmarkUpdateRequest;
use DDD\Domain\Benchmarks\Requests\BenchmarkStoreRequest;
use DDD\Domain\Benchmarks\Benchmark;
use DDD\App\Controllers\Controller;

class BenchmarkController extends Controller
{
    public function index()
    {
        $benchmarks = QueryBuilder::for(Benchmark::class)
            ->allowedFilters(['name', 'category.id'])
            // ->defaultSort('name')
            ->get();

        return BenchmarkResource::collection($benchmarks);
    }

    public function store(BenchmarkStoreRequest $request)
    {
        $benchmark = Benchmark::create($request->validated());

        return new BenchmarkResource($benchmark);
    }

    public function show(Benchmark $benchmark)
    {
        return new BenchmarkResource($benchmark);
    }

    public function update(Benchmark $benchmark, BenchmarkUpdateRequest $request)
    {
        $benchmark->update($request->validated());

        return new BenchmarkResource($benchmark);
    }

    public function destroy(Benchmark $benchmark)
    {
        $benchmark->delete();

        return new BenchmarkResource($benchmark);
    }
}
