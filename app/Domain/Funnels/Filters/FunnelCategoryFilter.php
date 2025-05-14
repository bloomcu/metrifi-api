<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelCategoryFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        // Join categories without resetting select columns
        $query->join('categories', 'funnels.category_id', '=', 'categories.id')
            ->where('categories.title', '=', $value); // Exact category match
    }
}
