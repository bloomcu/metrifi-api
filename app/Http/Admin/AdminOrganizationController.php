<?php

namespace DDD\Http\Admin;

use Illuminate\Http\Request;
use DDD\Http\Admin\Requests\AdminStoreOrganizationRequest;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class AdminOrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organizations = Organization::latest()->get();

        return OrganizationResource::collection($organizations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminStoreOrganizationRequest $request)
    {
        $organization = Organization::create($request->all());

        return new OrganizationResource($organization);
    }
}
