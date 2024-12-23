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

        // // If you want to allow filtering by exact match:
        // // e.g. ?filter[conversion_rate]=10.5
        // $query->whereRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') = ?", [(int) $value]);

        // Always greater than or equal to
        $query->whereRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') >= ?", [(int) $value]);

        /**
         * Alternatively, if you want a "greater than" or "less than" style filter,
         * you could parse the $value or handle it differently, e.g.:
         */
        // if (str_starts_with($value, 'gte:')) {
        //     // Greater than or equal to
        //     $comparisonValue = substr($value, 4);
        //     $query->whereRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') >= ?", [(int) $comparisonValue]);

        // } elseif (str_starts_with($value, 'gt:')) {
        //     // Greater than
        //     $comparisonValue = substr($value, 3);
        //     $query->whereRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') > ?", [$comparisonValue]);

        // } elseif (str_starts_with($value, 'lt:')) {
        //     // Less than
        //     $comparisonValue = substr($value, 3);
        //     $query->whereRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') < ?", [$comparisonValue]);

        // } else {
        //     // default exact match
        //     $query->whereRaw("JSON_EXTRACT(snapshots, '$.$period.conversion_rate') = ?", [$value]);
        // }
    }
}
