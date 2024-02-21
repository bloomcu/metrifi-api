<?php

use Illuminate\Support\Facades\Route;
use DDD\Http\Services\Google\GoogleAuthController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsDataController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsAdminController;
use DDD\Http\Funnels\FunnelStepController;
use DDD\Http\Funnels\FunnelGenerationController;
use DDD\Http\Funnels\FunnelController;
use DDD\Http\Funnels\FunnelAutomationsController;
use DDD\Http\Connections\ConnectionController;

Route::middleware('auth:sanctum')->group(function() {
    
    // Google Auth
    Route::prefix('google')->group(function() {
        Route::post('connect', [GoogleAuthController::class, 'connect']);
        Route::post('callback', [GoogleAuthController::class, 'callback']);
    });

    // Google Analytics admin
    Route::prefix('ga')->group(function() {
        Route::post('accounts', [GoogleAnalyticsAdminController::class, 'listAccounts']);
    });

    // Google Analytics data
    Route::prefix('ga')->group(function() {
        Route::post('page-views/{connection}', [GoogleAnalyticsDataController::class, 'fetchPageViews']);
        Route::post('outbound-clicks/{connection}', [GoogleAnalyticsDataController::class, 'fetchOutboundClicks']);
        Route::post('outbound-clicks-by-page-path/{connection}', [GoogleAnalyticsDataController::class, 'fetchOutboundClicksByPagePath']);
        // Route::post('report/{connection}', [GoogleAnalyticsDataController::class, 'runReport']);
        // Route::post('funnel/{connection}', [GoogleAnalyticsDataController::class, 'runFunnelReport']);
        // Route::post('export/{connection}', [GoogleAnalyticsDataExportToCSVController::class, 'exportReport']);
    });

    Route::prefix('{organization:slug}')->scopeBindings()->group(function() {
        // Connections
        Route::prefix('connections')->group(function() {
            Route::get('', [ConnectionController::class, 'index']);
            Route::post('', [ConnectionController::class, 'store']);
            Route::delete('/{connection}', [ConnectionController::class, 'destroy']);
        });

        // Funnel generation
        Route::prefix('generate')->group(function() {
            Route::get('/funnels/{connection}', [FunnelGenerationController::class, 'generateFunnels']);
            Route::get('/steps/{funnel}', [FunnelGenerationController::class, 'generateFunnelSteps']);
            Route::get('/outbound-links/{funnel}', [FunnelGenerationController::class, 'generateFunnelOutboundLinksMessage']);
        });

        // Funnels
        Route::prefix('funnels')->group(function() {
            Route::get('/', [FunnelController::class, 'index']);
            Route::post('/', [FunnelController::class, 'store']);
            Route::get('/{funnel}', [FunnelController::class, 'show']);
            Route::put('/{funnel}', [FunnelController::class, 'update']);
            Route::delete('/{funnel}', [FunnelController::class, 'destroy']);

            // Funnel steps
            Route::prefix('{funnel}/steps')->group(function() {
                Route::post('/', [FunnelStepController::class, 'store']);
                Route::put('/{step}', [FunnelStepController::class, 'update']);
                Route::delete('/{step}', [FunnelStepController::class, 'destroy']);
            });
        });
    }); 
});