<?php

namespace DDD\Http\Admin;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Http\Request;
use DDD\Http\Admin\Resources\AdminFunnelResource;
use DDD\Domain\Funnels\Sorts\FunnelUsersSort;
use DDD\Domain\Funnels\Sorts\FunnelStepsSort;
use DDD\Domain\Funnels\Sorts\FunnelOrganizationPrivacySort;
use DDD\Domain\Funnels\Sorts\FunnelConversionRateSort;
use DDD\Domain\Funnels\Sorts\FunnelCategorySort;
use DDD\Domain\Funnels\Sorts\FunnelAssetsSort;
use DDD\Domain\Funnels\Funnel;
use DDD\Domain\Funnels\Filters\FunnelUsersFilter;
use DDD\Domain\Funnels\Filters\FunnelStepsFilter;
use DDD\Domain\Funnels\Filters\FunnelOrganizationPrivacyFilter;
use DDD\Domain\Funnels\Filters\FunnelConversionRateFilter;
use DDD\Domain\Funnels\Filters\FunnelCategoryFilter;
use DDD\Domain\Funnels\Filters\FunnelAssetsFilter;
use DDD\Domain\Funnels\Actions\FunnelSnapshotAction;
use DDD\App\Controllers\Controller;

class AdminFunnelController extends Controller
{
    public function index(Request $request)
    {
        $funnels = QueryBuilder::for(Funnel::class)
            ->allowedSorts([
                AllowedSort::field('name'),
                AllowedSort::custom('assets', new FunnelAssetsSort()),
                AllowedSort::custom('conversion_rate', new FunnelConversionRateSort()),
                AllowedSort::custom('users', new FunnelUsersSort()),
                AllowedSort::custom('steps_count', new FunnelStepsSort()),
                AllowedSort::custom('privacy', new FunnelOrganizationPrivacySort()),
                AllowedSort::custom('category', new FunnelCategorySort()),
                AllowedSort::field('created', 'created_at'),
            ])
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::custom('assets', new FunnelAssetsFilter()),
                AllowedFilter::custom('conversion_rate', new FunnelConversionRateFilter()),
                AllowedFilter::custom('users', new FunnelUsersFilter()),
                AllowedFilter::custom('steps_count', new FunnelStepsFilter()),
                AllowedFilter::custom('privacy', new FunnelOrganizationPrivacyFilter()),
                AllowedFilter::custom('category', new FunnelCategoryFilter()),
            ])
            ->withCount('steps')
            ->paginate(20)
            ->appends(
                request()->query()
            );
        
        return AdminFunnelResource::collection($funnels);
    }

    public function snapshotAll()
    {
        Funnel::chunk(100, function ($funnels) {
            foreach ($funnels as $funnel) {
                FunnelSnapshotAction::dispatch($funnel, 'last28Days');
                FunnelSnapshotAction::dispatch($funnel, 'last90Days');
            }
        });
        
        return response()->json([
            'message' => 'Funnel snapshot jobs dispatched.'
        ]);
    }
}
