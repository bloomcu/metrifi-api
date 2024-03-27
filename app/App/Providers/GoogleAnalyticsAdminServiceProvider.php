<?php

namespace DDD\App\Providers;

use Illuminate\Support\ServiceProvider;
use DDD\App\Services\GoogleAnalyticsAdmin\GoogleAnalyticsAdminService;

class GoogleAnalyticsAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('GoogleAnalyticsAdminService', static fn (): GoogleAnalyticsAdminService => app(GoogleAnalyticsAdminService::class));
    }
}
