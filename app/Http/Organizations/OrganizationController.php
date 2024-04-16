<?php

namespace DDD\Http\Organizations;

use DDD\Domain\Base\Organizations\Resources\OrganizationResource;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class OrganizationController extends Controller
{
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        $organization->delete();

        return new OrganizationResource($organization);
    }
}
