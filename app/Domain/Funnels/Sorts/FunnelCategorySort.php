<?php

namespace DDD\Domain\Funnels\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class FunnelCategorySort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'desc' : 'asc';

        // Join the categories table and sort by the category name
        $query->join('categories', 'funnels.category_id', '=', 'categories.id')
              ->orderBy('categories.title', $direction)
              ->select('funnels.*'); // Ensure only funnel columns are selected to avoid conflicts
    }
}
