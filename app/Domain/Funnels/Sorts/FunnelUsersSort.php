<?php

namespace DDD\Domain\Funnels\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class FunnelUsersSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'desc' : 'asc';
        $query->orderByRaw("JSON_EXTRACT(snapshots, '$.last28Days.users') $direction");
    }
}
