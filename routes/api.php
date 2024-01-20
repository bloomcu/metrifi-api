<?php

use Illuminate\Support\Facades\Route;
use DDD\Http\Services\Google\GoogleAuthController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsDataController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsAdminController;
use DDD\Http\Funnels\FunnelStepController;
use DDD\Http\Funnels\FunnelController;
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
        Route::post('funnel/{connection}', [GoogleAnalyticsDataController::class, 'runFunnelReport']);
        Route::post('report/{connection}', [GoogleAnalyticsDataController::class, 'runReport']);
    });

    Route::prefix('{organization:slug}')->scopeBindings()->group(function() {
        // Connections
        Route::prefix('connections')->group(function() {
            Route::get('', [ConnectionController::class, 'index']);
            Route::post('', [ConnectionController::class, 'store']);
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