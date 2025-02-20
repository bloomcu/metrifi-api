<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelStepsFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        // Use a having clause to filter steps_count
        $query->having('steps_count', '>=', (int) $value);
    }
}
