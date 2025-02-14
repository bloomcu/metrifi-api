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
use DDD\Domain\Funnels\Resources\FunnelResource;
use DDD\Domain\Funnels\Requests\FunnelUpdateRequest;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Filters\FunnelUsersFilter;
use DDD\Domain\Funnels\Filters\FunnelStepsFilter;
use DDD\Domain\Funnels\Filters\FunnelConversionRateFilter;
use DDD\Domain\Funnels\Filters\FunnelCategoryFilter;
use DDD\Domain\Funnels\Filters\FunnelAssetsFilter;
use DDD\App\Controllers\Controller;

class FunnelController extends Controller
{
    public function index(Organization $organization)
    {
        $funnels = QueryBuilder::for(Funnel::class)
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
          ->withCount('steps')
          ->paginate(20)
          ->appends(
              request()->query()
          );

        return FunnelResource::collection($funnels);
    }

    public function store(Organization $organization, Request $request)
    {
        $funnel = $organization->funnels()->create([
            'user_id' => $request->user()->id,
            'connection_id' => $organization->connections->first()->id,
            'name' => $request->name,
            'zoom' => 0,
            'conversion_value' => $request->conversion_value,
            'projections' => $request->projections,
        ]);

        return new FunnelResource($funnel);
    }

    public function show(Organization $organization, Funnel $funnel)
    {
        return new FunnelResource($funnel);
    }

    public function update(Organization $organization, Funnel $funnel, FunnelUpdateRequest $request)
    {
        $funnel->update($request->validated());

        return new FunnelResource($funnel);
    }

    public function destroy(Organization $organization, Funnel $funnel)
    {
        $funnel->delete();

        return new FunnelResource($funnel);
    }
}
