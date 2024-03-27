<?php

namespace DDD\App\Providers;

use Illuminate\Support\ServiceProvider;
use DDD\App\Services\GoogleAuth\GoogleAuthService;

class GoogleAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('GoogleAuthService', static fn (): GoogleAuthService => app(GoogleAuthService::class));
    }
}
