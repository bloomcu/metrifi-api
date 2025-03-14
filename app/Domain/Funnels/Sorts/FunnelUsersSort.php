<?php

namespace DDD\Domain\Funnels\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class FunnelUsersSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'desc' : 'asc';

        $period = request()->input('period', 'last28Days');

        $query->orderByRaw("JSON_EXTRACT(snapshots, '$.$period.users') $direction");

        // Ensure unique results by grouping by funnel ID
        // $query->groupBy('funnels.id');
    }
}
