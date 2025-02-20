<?php

namespace DDD\Domain\Funnels\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class FunnelConversionRateSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'desc' : 'asc';

        $period = request()->input('period', 'last28Days');

        // $query->orderByRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') $direction");

        $query->select('funnels.*') // Ensure only funnels columns
          ->orderByRaw("JSON_EXTRACT(funnels.snapshots, '$.$period.conversion_rate') $direction")
          ->groupBy('funnels.id'); // Deduplicate within the sort
    }
}
