<?php

namespace DDD\Http\Funnels;

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
        // $funnels = Funnel::search($request->term)->get();
        // $funnels = Funnel::search($request->term)->paginate(10);

        $funnels = Funnel::search($request->term)->query(function ($query) {
            return $query->orderBy('name', 'desc');
        })->get();

        return FunnelPublicResource::collection($funnels);
    }
}
