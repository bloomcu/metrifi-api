<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelStepsFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        // Ensure the steps_count is available before filtering
        // $query->withCount('steps')
        //       ->having('steps_count', '>=', (int) $value);

        // Apply the steps count first
        $query->withCount('steps');

        // Use a having clause to filter steps_count
        $query->having('steps_count', '>=', (int) $value);
    }
}
