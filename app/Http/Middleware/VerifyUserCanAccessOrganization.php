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

            if ($user->organization_id !== $organization->id && $user->role !== RoleEnum::Admin) {
                return response()->json(['error' => 'Not authorized.'],403);
            }
        }

        return $next($request);

        // dd($request->user());

        // $project_id = Request::route()->parameter('project', null);

        // $project = $this->projectRepo->findOrFail($project_id);

        // if(true===Helper::isMyProject($project))
        // {
        //     return $next($request);
        // }

        // $message = 'IsMyProjectMiddleware';
        // return response(view('errors.not-authd', compact('message')), 403);
    }
}
