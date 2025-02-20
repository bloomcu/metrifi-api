<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelConversionRateFilter implements Filter
{
    /**
     * Apply the filter to the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value  The value passed in the query string for this filter
     * @param  string  $property  The name of the filter property (e.g. conversion_rate)
     * @return void
     */
    public function __invoke(Builder $query, $value, string $property)
    {
        // Grab the period from the request, default to last28Days if not provided
        $period = request()->input('period', 'last28Days');

        // Always greater than or equal to
        $query->whereRaw("CAST(JSON_EXTRACT(snapshots, '$.$period.conversion_rate') AS DECIMAL(10,5)) >= ?", [(float) $value]);

    }
}
