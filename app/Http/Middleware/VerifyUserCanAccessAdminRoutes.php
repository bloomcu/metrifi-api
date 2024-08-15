<?php

namespace DDD\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DDD\Domain\Users\Enums\RoleEnum;
use Closure;

class VerifyUserCanAccessAdminRoutes
{
    public function handle(Request $request, Closure $next)
    {
        // dd($request->is('admin/*'));
        // dd($request->routeIs('admin.*'));
        // dd(Route::current()->getName() == 'admin');

        // if ($request->route->uri->contains('admin/')) {
            $user = Auth::user();

            if ($user->role !== RoleEnum::Admin) {
                return response()->json(['error' => 'Not authorized.'],403);
            }
        // }

        return $next($request);
    }
}
