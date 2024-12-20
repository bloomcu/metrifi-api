<?php

namespace DDD\Domain\Funnels\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class FunnelOrganizationPrivacySort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $query->join('organizations', 'funnels.organization_id', '=', 'organizations.id')
            ->orderBy('organizations.is_private', $descending ? 'desc' : 'asc')
            ->select('funnels.*'); // Ensures only funnel columns are selected
    }
}
