<?php

namespace DDD\Http\Funnels;

use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Funnels\Resources\FunnelPublicResource;
use DDD\Domain\Funnels\Requests\FunnelUpdateRequest;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Controllers\Controller;

class FunnelSearchController extends Controller
{
    public function search(Organization $organization, Request $request)
    {
        $funnels = QueryBuilder::for(Funnel::class)
            ->allowedFilters(['name', 'category.id'])
            ->defaultSort('name')
            ->get();

        return FunnelPublicResource::collection($funnels);
    }
}
