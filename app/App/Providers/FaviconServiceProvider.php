<?php

namespace DDD\App\Providers;

use Illuminate\Support\ServiceProvider;

use DDD\App\Services\Favicon\IconHorse;
use DDD\App\Services\Favicon\FaviconInterface;

class FaviconServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(FaviconInterface::class, function ($app) {
            return new IconHorse();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
