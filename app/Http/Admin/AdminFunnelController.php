<?php

namespace DDD\Http\Admin;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Http\Request;
use DDD\Http\Admin\Resources\AdminFunnelResource;
use DDD\Domain\Funnels\Funnel;
use DDD\App\Controllers\Controller;

class AdminFunnelController extends Controller
{
    public function index(Request $request)
    {
        $funnels = QueryBuilder::for(Funnel::class)
            ->allowedFilters([
                AllowedFilter::exact('name', 'category.id')
            ])
            ->paginate(20)
            ->appends(
                request()->query()
            );

        return AdminFunnelResource::collection($funnels);
    }
}
