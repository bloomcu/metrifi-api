<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelCategoryFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        // Ensure we join the categories table to filter by the category title
        // $query->join('categories', 'funnels.category_id', '=', 'categories.id')
        //       ->where('categories.title', 'like', "%{$value}%")
        //       ->select('funnels.*'); // Avoid conflicts by selecting only funnel columns

        // Join categories without resetting select columns
        $query->join('categories', 'funnels.category_id', '=', 'categories.id')
            ->where('categories.title', 'like', "%{$value}%");
    }
}
