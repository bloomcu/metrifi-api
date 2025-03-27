<?php
// TODO: Rename connections to connections

namespace DDD\Http\Connections;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DDD\Domain\Organizations\Organization;
use DDD\Domain\Connections\Resources\ConnectionResource;
use DDD\Domain\Connections\Connection;
use DDD\App\Controllers\Controller;
use Illuminate\Support\Str;

class ConnectionController extends Controller
{
    public function index(Organization $organization)
    {   
        $connections = $organization->connections->loadCount('funnels');
        
        return ConnectionResource::collection($connections);
    }

    public function store(Organization $organization, Request $request)
    {
        // Handle WordPress Website connections
        if ($request->service === 'WordPress Website') {
            return $this->storeWordPressConnection($organization, $request);
        }

        // Handle Google Analytics connections
        if ($request->service === 'Google Analytics - Property') {
            return $this->storeGoogleAnalyticsConnection($organization, $request);
        }
    }

    /**
     * Store a WordPress connection.
     *
     * @param Organization $organization
     * @param Request $request
     * @return ConnectionResource
     */
    protected function storeWordPressConnection(Organization $organization, Request $request)
    {
        // Validate WordPress connection
        $validator = Validator::make($request->all(), [
            'service' => 'required|string|in:WordPress Website',
            'name' => 'required|string|max:255',
            'token' => 'required|array',
            'token.wordpress_url' => 'required|string|url',
            'token.username' => 'required|string',
            'token.app_password' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $connection = $organization->connections()->create([
            'user_id' => auth()->user()->id,
            'service' => 'WordPress Website',
            'account_name' => null,
            'name' => $request->name,
            'uid' => (string) Str::uuid(),
            'token' => [
                'wordpress_url' => $request->token['wordpress_url'],
                'username' => $request->token['username'],
                'app_password' => $request->token['app_password'],
            ],
        ]);

        return new ConnectionResource($connection);
    }

    /**
     * Store a Google Analytics connection.
     *
     * @param Organization $organization
     * @param Request $request
     * @return ConnectionResource
     */
    protected function storeGoogleAnalyticsConnection(Organization $organization, Request $request)
    {
        // Validate Google Analytics connection
        $validator = Validator::make($request->all(), [
            'service' => 'required|string',
            'account_name' => 'nullable|string',
            'name' => 'required|string|max:255',
            'uid' => 'required|string',
            'token' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
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
