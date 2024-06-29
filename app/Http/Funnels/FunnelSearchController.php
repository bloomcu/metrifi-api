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
        // Private organization cannot see other funnels
        if ($organization->is_private) {
            $funnels = QueryBuilder::for(Funnel::class)
                ->allowedFilters(['name', 'category.id'])
                ->where('organization_id', $organization->id)
                ->defaultSort('name')
                ->get();

        } else {
            $funnels = QueryBuilder::for(Funnel::class)
                ->allowedFilters(['name', 'category.id'])
                ->whereRelation('organization', 'is_private', false) // Only return anonymous funnels
                ->defaultSort('name')
                ->get();
        }

        return FunnelPublicResource::collection($funnels);
    }
}
