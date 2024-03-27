<?php

namespace DDD\App\Providers;

use Illuminate\Support\ServiceProvider;
use DDD\App\Services\GoogleAnalyticsData\GoogleAnalyticsDataService;

class GoogleAnalyticsDataServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        app()->singleton('GoogleAnalyticsDataService', static fn (): GoogleAnalyticsDataService => app(GoogleAnalyticsDataService::class));
    }
}
