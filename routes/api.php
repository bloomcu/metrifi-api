<?php

use Illuminate\Support\Facades\Route;
use DDD\Http\Users\UserController;
use DDD\Http\Services\Google\GoogleAuthController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsDataController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsAdminController;
use DDD\Http\Organizations\OrganizationController;
use DDD\Http\Funnels\FunnelStepController;
use DDD\Http\Funnels\FunnelSnapshotController;
use DDD\Http\Funnels\FunnelSearchController;
use DDD\Http\Funnels\FunnelReplicateController;
use DDD\Http\Funnels\FunnelGenerationController;
use DDD\Http\Funnels\FunnelController;
use DDD\Http\Dashboards\DashboardFunnelController;
use DDD\Http\Dashboards\DashboardController;
use DDD\Http\Connections\ConnectionController;
use DDD\Http\Benchmarks\BenchmarkController;
use DDD\Http\Benchmarks\BenchmarkCalculateController;
use DDD\Http\Analyses\AnalysisController;

Route::middleware('auth:sanctum')->group(function() {

    // Benchmarks
    Route::prefix('benchmarks')->group(function () {
        Route::get('/', [BenchmarkController::class, 'index']);
        Route::post('/', [BenchmarkController::class, 'store']);
        Route::get('/{benchmark}', [BenchmarkController::class, 'show']);
        Route::put('/{benchmark}', [BenchmarkController::class, 'update']);
        Route::delete('/{benchmark}', [BenchmarkController::class, 'destroy']);

        // Calculate
        Route::prefix('{benchmark}/calculate')->group(function() {
            Route::get('/', [BenchmarkCalculateController::class, 'calculate']);
        });
    });

    // Organizations
    Route::prefix('organizations')->group(function () {
        Route::get('/', [OrganizationController::class, 'index']);
        Route::post('/', [OrganizationController::class, 'store']);
        Route::get('/{organization:slug}', [OrganizationController::class, 'show']);
        Route::put('/{organization:slug}', [OrganizationController::class, 'update']);
        Route::delete('/{organization:slug}', [OrganizationController::class, 'destroy']);
    });
    
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
        // Funnel report
        Route::post('funnel-report/{funnel}', [GoogleAnalyticsDataController::class, 'funnelReport']);
        
        // Page users
        Route::post('page-users/{connection}', [GoogleAnalyticsDataController::class, 'pageUsers']);
        Route::post('page-plus-query-string-users/{connection}', [GoogleAnalyticsDataController::class, 'pagePlusQueryStringUsers']);

        // Outbound link users
        Route::post('outbound-link-users/{connection}', [GoogleAnalyticsDataController::class, 'outboundLinkUsers']);
        Route::post('outbound-link-by-page-path-users/{connection}', [GoogleAnalyticsDataController::class, 'outboundLinkByPagePathUsers']);

        // Form users
        // Route::post('form-start-users/{connection}', [GoogleAnalyticsDataController::class, 'formStartUsers']);
        Route::post('form-user-submissions/{connection}', [GoogleAnalyticsDataController::class, 'formUserSubmissions']);
    });

    Route::prefix('{organization:slug}')->scopeBindings()->group(function() {
        // Users
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::delete('/{user}', [UserController::class, 'destroy']);
        });

        // Connections
        Route::prefix('connections')->group(function() {
            Route::get('', [ConnectionController::class, 'index']);
            Route::post('', [ConnectionController::class, 'store']);
            Route::delete('/{connection}', [ConnectionController::class, 'destroy']);
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

            // Funnel snapshot
            Route::prefix('{funnel}/snapshot')->group(function() {
                Route::get('/refresh', [FunnelSnapshotController::class, 'refresh']);
            });
        });

        // Funnel generation
        Route::prefix('generate')->group(function() {
            Route::get('/funnels/{connection}', [FunnelGenerationController::class, 'generateFunnels']);
            Route::get('/steps/{funnel}', [FunnelGenerationController::class, 'generateFunnelSteps']);
            Route::get('/outbound-links/{funnel}', [FunnelGenerationController::class, 'generateFunnelOutboundLinksMessage']);
        });

        // Funnel replicate
        Route::prefix('funnels-replicate')->group(function() {
            Route::post('/{funnel}', [FunnelReplicateController::class, 'replicate']);
        });

        // Funnel search
        Route::prefix('funnels-search')->group(function() {
            Route::get('/', [FunnelSearchController::class, 'search']);
        });

        // Dashboards
        Route::prefix('dashboards')->group(function() {
            Route::get('/', [DashboardController::class, 'index']);
            Route::post('/', [DashboardController::class, 'store']);
            Route::get('/{dashboard}', [DashboardController::class, 'show']);
            Route::put('/{dashboard}', [DashboardController::class, 'update']);
            Route::delete('/{dashboard}', [DashboardController::class, 'destroy']);
            
            // Dashboard funnels
            Route::prefix('{dashboard}/funnels')->group(function() {
                Route::post('/attach', [DashboardFunnelController::class, 'attach']);
                Route::post('/detach', [DashboardFunnelController::class, 'detach']);
                Route::post('/reorder', [DashboardFunnelController::class, 'reorder']);
                Route::post('/{funnel}/toggle-step', [DashboardFunnelController::class, 'toggleStep']);
                Route::post('/{funnel}/enable-steps', [DashboardFunnelController::class, 'enableSteps']);
            });
        });

        // Analyses
        Route::prefix('/dashboards/{dashboard}/analyses')->group(function() {
            Route::get('/', [AnalysisController::class, 'index']);
            Route::post('/', [AnalysisController::class, 'store']);
            Route::get('/{analysis}', [AnalysisController::class, 'show']);
            Route::put('/{analysis}', [AnalysisController::class, 'update']);
        });
    }); 
});