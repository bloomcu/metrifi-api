<?php

namespace DDD\App\Providers;

use Illuminate\Support\ServiceProvider;

use DDD\App\Services\Scraper\HttpScraper;
use DDD\App\Services\Scraper\ScraperInterface;

class ScraperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ScraperInterface::class, function ($app) {
            return new HttpScraper();
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
