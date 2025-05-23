<?php

namespace DDD\Http\Base\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use DDD\Http\Base\Auth\Requests\AuthRegisterWithInvitationRequest;
use DDD\Domain\Users\User;
use DDD\Domain\Organizations\Resources\OrganizationResource;
use DDD\Domain\Base\Invitations\Invitation;
use DDD\App\Controllers\Controller;

class AuthRegisterWithInvitationController extends Controller
{
    public function __invoke(Invitation $invitation, AuthRegisterWithInvitationRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'role' => 'editor', // TODO: Remove
            'settings' => ['send_weekly_website_analysis' => true],
            'organization_id' => $invitation->organization->id,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $invitation->delete();

        return response()->json([
            'message' => 'Registration successful',
            'data' => [
                'access_token' => $token,
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'settings' => $user->settings,
                'organization' => new OrganizationResource($user->organization),
            ],
        ], 200);
    }
}
