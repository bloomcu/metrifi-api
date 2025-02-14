<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelOrganizationFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
      // Ensure that a valid slug is provided
      if (!is_string($value) || empty($value)) {
        return;
      }

      $query->whereHas('organization', function (Builder $subQuery) use ($value) {
        $subQuery->where('slug', $value);
      });
    }
}
