<?php

use Illuminate\Support\Facades\Route;
use DDD\Http\Users\UserController;
use DDD\Http\Stripe\StripeController;
use DDD\Http\Services\WordPress\WordPressPageController;
use DDD\Http\Services\Google\GoogleAuthController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsDataController;
use DDD\Http\Services\GoogleAnalytics\GoogleAnalyticsAdminController;
use DDD\Http\Recommendations\RecommendationGenerateController;
use DDD\Http\Recommendations\RecommendationFileController;
use DDD\Http\Recommendations\RecommendationReplicateController;
use DDD\Http\Recommendations\RecommendationController;
use DDD\Http\Pages\PageController;
use DDD\Http\Organizations\OrganizationWeeklyAnalysisEmailController;
use DDD\Http\Organizations\OrganizationSubscriptionController;
use DDD\Http\Organizations\OrganizationController;
use DDD\Http\Organizations\OrganizationAnalysisController;
use DDD\Http\Funnels\FunnelStepController;
use DDD\Http\Funnels\FunnelSnapshotController;
use DDD\Http\Funnels\FunnelSearchController;
use DDD\Http\Funnels\FunnelReplicateController;
use DDD\Http\Funnels\FunnelGenerationController;
use DDD\Http\Funnels\FunnelController;
use DDD\Http\Files\FileController;
use DDD\Http\Dashboards\DashboardReplicateController;
use DDD\Http\Dashboards\DashboardFunnelController;
use DDD\Http\Dashboards\DashboardController;
use DDD\Http\Connections\ConnectionController;
use DDD\Http\Chats\ChatsController;
use DDD\Http\Blocks\BlockRegenerationController;
use DDD\Http\Blocks\BlockVersionController;
use DDD\Http\Blocks\BlockOrderController;
use DDD\Http\Blocks\BlockController;
use DDD\Http\Blocks\ReplicateBlockController;
use DDD\Http\Benchmarks\BenchmarkController;
use DDD\Http\Benchmarks\BenchmarkCalculateController;
use DDD\Http\Analyses\AnalysisController;
use DDD\Http\Admin\AdminOrganizationController;
use DDD\Http\Admin\AdminFunnelController;
use DDD\Http\Admin\AdminDashboardController;

Route::middleware('auth:sanctum')->group(function() {
    // Admin
    Route::prefix('admin')->middleware(['canAccessAdminArea'])->group(function () {
        // Dashboards
        Route::prefix('dashboards')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index']);
            Route::get('/analyze', [AdminDashboardController::class, 'analyzeAll']);
        });

        // Funnels
        Route::prefix('funnels')->group(function () {
            Route::get('/', [AdminFunnelController::class, 'index']);
            Route::get('/snapshot', [AdminFunnelController::class, 'snapshotAll']);
        });
        
        // Organizations
        Route::prefix('organizations')->group(function () {
            Route::get('/', [AdminOrganizationController::class, 'index']);
            Route::post('/', [AdminOrganizationController::class, 'store']);
            Route::put('/{organization}', [AdminOrganizationController::class, 'update']);
        });
    });

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
        Route::post('page-title-users/{connection}', [GoogleAnalyticsDataController::class, 'pageTitleUsers']);

        // Outbound link users
        Route::post('outbound-link-users/{connection}', [GoogleAnalyticsDataController::class, 'outboundLinkUsers']);
        Route::post('outbound-link-by-page-path-users/{connection}', [GoogleAnalyticsDataController::class, 'outboundLinkByPagePathUsers']);

        // Form users
        Route::post('form-user-submissions/{connection}', [GoogleAnalyticsDataController::class, 'formUserSubmissions']);

        // LLM users
        Route::post('llm-users/{connection}', [GoogleAnalyticsDataController::class, 'llmUsers']);
    });

    // Organizations
    Route::prefix('organizations')->group(function () {
      Route::get('/{organization:slug}', [OrganizationController::class, 'show']);
      Route::put('/{organization:slug}', [OrganizationController::class, 'update']);
      Route::delete('/{organization:slug}', [OrganizationController::class, 'destroy']);

      // Analyze organization dashboards
      Route::post('{organization:slug}/analyze', [OrganizationAnalysisController::class, 'analyzeOrganizationDashboards']);

      // Subscription
      Route::get('{organization:slug}/subscription', [OrganizationSubscriptionController::class, 'show']);

      // Weekly analysis email - disabled while investigating duplicate email issue
      // Route::get('{organization:slug}/weekly-analysis-email', [OrganizationWeeklyAnalysisEmailController::class, 'send']);
    });

    Route::prefix('{organization:slug}')->group(function() {
        // Stripe
        Route::prefix('stripe')->group(function() {
            Route::post('/checkout', [StripeController::class, 'checkout']);
            Route::post('/billing', [StripeController::class, 'billing']);
            Route::post('/update', [StripeController::class, 'update']);
            Route::post('/cancel', [StripeController::class, 'cancel']);
        });

        // Users
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::post('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);
        });

        // Files
        Route::prefix('files')->group(function () {
            Route::get('/', [FileController::class, 'index']);
            Route::post('/', [FileController::class, 'store']);
            Route::get('/{file}', [FileController::class, 'show']);
            Route::post('/{file}', [FileController::class, 'update']);
            Route::delete('/{file}', [FileController::class, 'destroy']);
        });

        // Chat
        Route::prefix('chats')->group(function() {
          Route::post('/', [ChatsController::class, 'store']);
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

        // Replicate dashboard
        Route::prefix('dashboards-replicate')->group(function() {
          Route::post('/{dashboard}', [DashboardReplicateController::class, 'replicate']);
      });

        // Analyses
        Route::prefix('/dashboards/{dashboard}/analyses')->group(function() {
            Route::get('/', [AnalysisController::class, 'index']);
            Route::post('/', [AnalysisController::class, 'store']);
            Route::get('/{analysis}', [AnalysisController::class, 'show']);
            Route::put('/{analysis}', [AnalysisController::class, 'update']);
        });

        // Recommendations
        Route::prefix('/recommendations')->group(function() {
            Route::get('/', [RecommendationController::class, 'index']);
            Route::post('/', [RecommendationController::class, 'store']);
            Route::get('/{recommendation}', [RecommendationController::class, 'show']);
            Route::put('/{recommendation}', [RecommendationController::class, 'update']);
            Route::delete('/{recommendation}', [RecommendationController::class, 'destroy']);

            // Recommendation files
            Route::post('/{recommendation}/files', [RecommendationFileController::class, 'attach']);

            // Recommendation generate
            Route::put('/{recommendation}/generate', [RecommendationGenerateController::class, 'update']);
            
            // Recommendation replicate
            Route::post('/{recommendation}/replicate', [RecommendationReplicateController::class, 'store']);
        });

        // Pages
        Route::prefix('pages')->group(function() {
            Route::post('/', [PageController::class, 'store']);
            Route::get('/{page}', [PageController::class, 'show']);
            Route::put('/{page}', [PageController::class, 'update']);
            Route::delete('/{page}', [PageController::class, 'destroy']);
        });

        // Blocks
        Route::prefix('blocks')->group(function() {
            Route::post('/', [BlockController::class, 'store']);
            Route::get('/{block}', [BlockController::class, 'show']);
            Route::put('/{block}', [BlockController::class, 'update']);
            Route::delete('/{block}', [BlockController::class, 'destroy']);

            // Reorder block
            Route::put('/{block}/reorder', [BlockOrderController::class, 'reorder']);

            // Regenerate block html
            Route::put('/{block}/regenerate', [BlockRegenerationController::class, 'store']);
            
            // Replicate block
            Route::post('/{block}/replicate', [ReplicateBlockController::class, 'replicate']);
            
            // Block versions
            Route::put('/{block}/versions/{version}', [BlockVersionController::class, 'revert']);
        });

        // WordPress
        Route::prefix('wordpress')->group(function() {
            Route::post('/pages', [WordPressPageController::class, 'store']);
        });
    }); 
});
