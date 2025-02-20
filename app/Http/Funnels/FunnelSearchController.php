<?php

namespace DDD\Http\Funnels;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Sorts\FunnelUsersSort;
use DDD\Domain\Funnels\Sorts\FunnelStepsSort;
use DDD\Domain\Funnels\Sorts\FunnelConversionRateSort;
use DDD\Domain\Funnels\Sorts\FunnelCategorySort;
use DDD\Domain\Funnels\Sorts\FunnelAssetsSort;
use DDD\Domain\Funnels\Resources\FunnelPublicResource;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Filters\FunnelUsersFilter;
use DDD\Domain\Funnels\Filters\FunnelStepsFilter;
use DDD\Domain\Funnels\Filters\FunnelOrganizationFilter;
use DDD\Domain\Funnels\Filters\FunnelConversionRateFilter;
use DDD\Domain\Funnels\Filters\FunnelCategoryFilter;
use DDD\Domain\Funnels\Filters\FunnelAssetsFilter;
use DDD\App\Controllers\Controller;

class FunnelSearchController extends Controller
{
    public function search(Organization $organization, Request $request)
    {   
        // Private organization cannot see other funnels
        if ($organization->is_private) {
            $query = QueryBuilder::for(Funnel::class)
                ->where('organization_id', $organization->id)
                ->allowedSorts([
                  AllowedSort::field('name'),
                  AllowedSort::custom('assets', new FunnelAssetsSort()),
                  AllowedSort::custom('conversion_rate', new FunnelConversionRateSort()),
                  AllowedSort::custom('users', new FunnelUsersSort()),
                  AllowedSort::custom('steps_count', new FunnelStepsSort()),
                  AllowedSort::custom('category', new FunnelCategorySort()),
                  AllowedSort::field('created', 'created_at'),
              ])
              ->allowedFilters([
                  AllowedFilter::partial('name'),
                  AllowedFilter::custom('assets', new FunnelAssetsFilter()),
                  AllowedFilter::custom('conversion_rate', new FunnelConversionRateFilter()),
                  AllowedFilter::custom('users', new FunnelUsersFilter()),
                  AllowedFilter::custom('steps_count', new FunnelStepsFilter()),
                  AllowedFilter::custom('category', new FunnelCategoryFilter()),
              ])
              ->withCount('steps');

        } else {
            $query = QueryBuilder::for(Funnel::class)
                ->whereRelation('organization', 'is_private', false) // Only return anonymous funnels
                ->allowedSorts([
                  AllowedSort::field('name'),
                  AllowedSort::custom('assets', new FunnelAssetsSort()),
                  AllowedSort::custom('conversion_rate', new FunnelConversionRateSort()),
                  AllowedSort::custom('users', new FunnelUsersSort()),
                  AllowedSort::custom('steps_count', new FunnelStepsSort()),
                  AllowedSort::custom('category', new FunnelCategorySort()),
                  AllowedSort::field('updated', 'updated_at'),
              ])
              ->allowedFilters([
                  AllowedFilter::partial('name'),
                  AllowedFilter::custom('organization', new FunnelOrganizationFilter()),
                  AllowedFilter::custom('assets', new FunnelAssetsFilter()),
                  AllowedFilter::custom('conversion_rate', new FunnelConversionRateFilter()),
                  AllowedFilter::custom('users', new FunnelUsersFilter()),
                  AllowedFilter::custom('steps_count', new FunnelStepsFilter()),
                  AllowedFilter::custom('category', new FunnelCategoryFilter()),
              ])
              ->withCount('steps');
        }
        

        // Ids from all funnels before pagination
        $allIds = (clone $query)->pluck('id');

        // Paginate
        $funnels = $query->paginate(30)->appends(
            request()->query()
        );

        // return FunnelPublicResource::collection($funnels)->additional([
        //     'meta' => [
        //         'all_ids' => $allIds
        //     ]
        // ]);

        return FunnelPublicResource::collection($funnels);
    }
}
