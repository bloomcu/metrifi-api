<?php

use Illuminate\Support\Facades\Route;
use DDD\Http\Connections\ConnectionController;
use DDD\Http\Connections\Google\GoogleAuthController;
use DDD\Http\Connections\Google\GoogleAnalyticsAdminController;
use DDD\Http\Analytics\AnalyticsController;

Route::middleware('auth:sanctum')->group(function() {
    
    // Google Auth
    Route::prefix('google')->group(function() {
        Route::post('connect', [GoogleAuthController::class, 'connect']);
        Route::post('callback', [GoogleAuthController::class, 'callback']);
    });

    // Google Analytics Admin
    Route::prefix('ga')->group(function() {
        Route::post('accounts', [GoogleAnalyticsAdminController::class, 'listAccounts']);
    });

    Route::prefix('{organization:slug}')->scopeBindings()->group(function() {
        // Connections
        Route::prefix('connections')->group(function() {
            Route::get('', [ConnectionController::class, 'index']);
            Route::post('', [ConnectionController::class, 'store']);
        });

        // Analytics
        Route::prefix('analytics/{connection}')->group(function() {
            Route::get('', [AnalyticsController::class, 'runReport']);
        });
    }); 
});