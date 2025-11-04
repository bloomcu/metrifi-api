<?php

namespace DDD\Http\Admin;

use Illuminate\Http\Request;
use DDD\Http\Admin\Requests\AdminUpdateOrganizationRequest;
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

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminUpdateOrganizationRequest $request, Organization $organization)
    {
        $organization->update($request->validated());

        return new OrganizationResource($organization);
    }
}
