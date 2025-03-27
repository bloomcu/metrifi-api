<?php

namespace DDD\Http\Users;

use DDD\Domain\Users\User;
use DDD\Domain\Users\Resources\UserResource;
use DDD\Domain\Users\Requests\UpdateUserRequest;
use DDD\Domain\Users\Enums\RoleEnum;
use DDD\Domain\Organizations\Organization;
use DDD\App\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Organization $organization)
    {
        $users = $organization->users()->latest()->get();

        return UserResource::collection($users);
    }

    public function show(Organization $organization, User $user)
    {
        return new UserResource($user);
    }

    public function update(Organization $organization, User $user, Request $request)
    {
        // check if user is this authenticated user
        if ($user->id !== auth()->id()) {
            return response()->json(['error' => 'Not authorized.'],403);
        }
        
        $user->update($request->all());
    
        return new UserResource($user);
    }
    
    public function destroy(Organization $organization, User $user)
    {
        // Check if user role is admin
        if ($user->role == RoleEnum::Admin) {
            return response()->json(['error' => 'Not authorized to remove admins.'], 403);
        }

        $user->delete();
    
        return new UserResource($user);
    }
}
