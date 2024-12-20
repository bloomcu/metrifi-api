<?php

namespace DDD\Domain\Funnels\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FunnelOrganizationPrivacyFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        // Chatgpt
        // return $query->whereHas('organization', function (Builder $query) use ($value) {
        //     $query->where('is_private', filter_var($value, FILTER_VALIDATE_BOOLEAN));
        // });

        // Gemini
        return $query->whereHas('organization', function (Builder $query) use ($value) {
            if (is_array($value)) {
                $query->whereIn('is_private', $value);
            } else {
                $query->where('is_private', $value);
            }
        });
    }
}
