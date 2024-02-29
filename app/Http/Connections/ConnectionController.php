<?php
// TODO: Rename connections to connections

namespace DDD\Http\Connections;

use Illuminate\Http\Request;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Connections\Resources\ConnectionResource;
use DDD\Domain\Connections\Connection;
use DDD\App\Controllers\Controller;

class ConnectionController extends Controller
{
    public function index(Organization $organization)
    {   
        $connections = $organization->connections->loadCount('funnels');
        
        return ConnectionResource::collection($connections);
    }

    public function store(Organization $organization, Request $request)
    {
        $connection = $organization->connections()->create([
            'user_id' => auth()->user()->id,
            'service' => $request->service,
            'account_name' => $request->account_name,
            'name' => $request->name,
            'uid' => $request->uid,
            'token' => $request->token,
        ]);

        return new ConnectionResource($connection);
    }

    public function destroy(Organization $organization, Connection $connection)
    {
        $connection->delete();

        return new ConnectionResource($connection);
    }
}
