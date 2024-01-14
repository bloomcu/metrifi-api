<?php

use Illuminate\Support\Facades\Route;
use DDD\Http\Integrations\IntegrationController;
use DDD\Http\Integrations\Google\GoogleAuthController;
use DDD\Http\Integrations\Google\GoogleAnalyticsAdminController;
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
        // Integrations
        Route::prefix('integrations')->group(function() {
            Route::get('', [IntegrationController::class, 'index']);
            Route::post('', [IntegrationController::class, 'store']);
        });

        // Analytics
        Route::prefix('analytics/{integration}')->group(function() {
            Route::get('', [AnalyticsController::class, 'runReport']);
        });
    }); 
});