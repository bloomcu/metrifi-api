<?php
// TODO: Rename integrations to connections

namespace DDD\Http\Integrations;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class IntegrationController extends Controller
{
    public function index(Organization $organization)
    {   
        return response()->json([
            'data' => $organization->integrations
        ], 200);
    }

    public function store(Organization $organization, Request $request)
    {
        $integration = $organization->integrations()->create([
            'user_id' => auth()->user()->id,
            'service' => $request->service,
            'name' => $request->name,
            'uid' => $request->uid,
            'token' => $request->token,
        ]);

        return response()->json([
            'data' => $integration
        ], 200);
    }
}
