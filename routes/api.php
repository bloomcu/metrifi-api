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
        // Route::post('report/{connection}', [GoogleAnalyticsDataController::class, 'runReport']);
        // Route::post('funnel/{connection}', [GoogleAnalyticsDataController::class, 'runFunnelReport']);
        // Route::post('export/{connection}', [GoogleAnalyticsDataExportToCSVController::class, 'exportReport']);
    });

    Route::prefix('{organization:slug}')->scopeBindings()->group(function() {
        // Connections
        Route::prefix('connections')->group(function() {
            Route::get('', [ConnectionController::class, 'index']);
            Route::post('', [ConnectionController::class, 'store']);
        });

        // Generate funnel
        Route::prefix('generate/{connection}')->group(function() {
            Route::get('/', [FunnelGenerationController::class, 'run']);
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

            // Funnel automations
            Route::prefix('{funnel}/automations')->group(function() {
                Route::get('/segment-terminal-page-path', [FunnelAutomationsController::class, 'segmentTerminalPagePath']);
                Route::get('/validate-page-paths', [FunnelAutomationsController::class, 'validatePagePaths']);
            });
        });
    }); 
});