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

        $query->orderByRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') $direction");

        // $query->select('funnels.*') // Ensure only funnels columns
        //   ->orderByRaw("JSON_EXTRACT(funnels.snapshots, '$.$period.conversion_rate') $direction")
        //   ->groupBy('funnels.id'); // Deduplicate within the sort

      //   $query->orderByRaw("(
      //     SELECT JSON_EXTRACT(snapshots, '$.$period.conversion_rate')
      //     FROM funnels f2
      //     WHERE f2.id = funnels.id
      //     LIMIT 1
      // ) $direction");

        // Use JSON_UNQUOTE or COALESCE to handle arrays/nulls
      // $query->orderByRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(funnels.snapshots, '$.$period.conversion_rate')), 0) $direction");
    }
}
