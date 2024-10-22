<?php

namespace DDD\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DDD\Domain\Users\Enums\RoleEnum;
use Closure;

class VerifyUserCanAccessOrganization
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->route('organization')) {
            $organization = $request->route('organization');
            $user = Auth::user();
            // dd($organization);

            if ($user->organization->slug !== $organization->slug && $user->role !== RoleEnum::Admin) {
                return response()->json(['error' => 'Not authorized.'],403);
            }
        }

        return $next($request);
    }
}
