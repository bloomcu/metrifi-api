<?php

namespace DDD\Http\Organizations;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Organizations\Requests\UpdateOrganizationRequest;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class OrganizationController extends Controller
{

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        return new OrganizationResource($organization->load(['funnels', 'connections']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Organization $organization, UpdateOrganizationRequest $request)
    {
        $organization->update($request->all());

        return new OrganizationResource($organization);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        $organization->delete();

        return new OrganizationResource($organization);
    }
}
