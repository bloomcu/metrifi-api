<?php
// TODO: Rename connections to connections

namespace DDD\Http\Connections;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;

class ConnectionController extends Controller
{
    public function index(Organization $organization)
    {   
        return response()->json([
            'data' => $organization->connections
        ], 200);
    }

    public function store(Organization $organization, Request $request)
    {
        $connection = $organization->connections()->create([
            'user_id' => auth()->user()->id,
            'service' => $request->service,
            'name' => $request->name,
            'uid' => $request->uid,
            'token' => $request->token,
        ]);

        return response()->json([
            'data' => $connection
        ], 200);
    }
}
