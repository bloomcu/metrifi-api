<?php

namespace DDD\App\Providers;

use Illuminate\Support\ServiceProvider;

use DDD\App\Services\Screenshot\Thumbio;
use DDD\App\Services\Screenshot\ScreenshotOne;
use DDD\App\Services\Screenshot\ScreenshotInterface;
use DDD\App\Services\Screenshot\ApiFlash;

class ScreenshotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ScreenshotInterface::class, function ($app) {
            // return new Thumbio(
            //     token: config('services.thumbio.token'),
            // );
            // return new ScreenshotOne(
            //     accesskey: config('services.screenshotone.accesskey'),
            // );
            return new ApiFlash(
                accesskey: config('services.apiflash.accesskey'),
            );
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
