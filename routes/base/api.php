<?php

use DDD\Http\Base\Auth\AuthLoginController;
use DDD\Http\Base\Auth\AuthLogoutController;
use DDD\Http\Base\Auth\AuthMeController;
use DDD\Http\Base\Auth\AuthPasswordForgotController;
use DDD\Http\Base\Auth\AuthPasswordResetController;
use DDD\Http\Base\Auth\AuthRegisterController;
use DDD\Http\Base\Auth\AuthRegisterWithInvitationController;
use DDD\Http\Base\Categories\CategoryController;
use DDD\Http\Base\Invitations\InvitationController;
use DDD\Http\Base\Subscriptions\Intent\IntentController;
use DDD\Http\Base\Subscriptions\Plans\PlanController;
use DDD\Http\Base\Subscriptions\Plans\PlanSwapAvailabilityController;
use DDD\Http\Base\Subscriptions\Subscriptions\SubscriptionController;
use DDD\Http\Base\Tags\TagController;
use DDD\Http\Base\Teams\TeamController;
use Illuminate\Support\Facades\Route;

// Public - Auth
Route::post('auth/login', AuthLoginController::class);
Route::post('auth/register', AuthRegisterController::class);
Route::post('auth/register/invitation/{invitation:uuid}', AuthRegisterWithInvitationController::class);
Route::post('auth/password/forgot', AuthPasswordForgotController::class);
Route::post('auth/password/reset', AuthPasswordResetController::class);

// Public - Invitations
Route::get('/invitations/{invitation:uuid}', [InvitationController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('auth/logout', AuthLogoutController::class);
    Route::get('auth/me', AuthMeController::class);

    // Route::prefix('{organization:slug}')->middleware(['organization'])->scopeBindings()->group(function() {
    Route::prefix('{organization:slug}')->scopeBindings()->group(function () {
        // Subscriptions
        // Route::prefix('subscriptions')->group(function () {
        //     Route::get('/intent', IntentController::class);
        //     Route::get('/plans', [PlanController::class, 'index']);
        //     Route::get('/plans/availability', PlanSwapAvailabilityController::class);
        //     Route::post('/subscriptions', [SubscriptionController::class, 'store']);
        //     Route::patch('/subscriptions', [SubscriptionController::class, 'update']);
        // });

        // Invitations
        Route::prefix('invitations')->group(function () {
            Route::get('/', [InvitationController::class, 'index']);
            Route::post('/', [InvitationController::class, 'store']);
            Route::delete('/{invitation:uuid}', [InvitationController::class, 'destroy']);
        });

        // Teams
        Route::prefix('teams')->group(function () {
            Route::get('/', [TeamController::class, 'index']);
            Route::post('/', [TeamController::class, 'store']);
            Route::get('/{team:slug}', [TeamController::class, 'show']);
            Route::put('/{team:slug}', [TeamController::class, 'update']);
            Route::delete('/{team:slug}', [TeamController::class, 'destroy']);
        });
    });

    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{category}', [CategoryController::class, 'show']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });

    // Tags
    Route::prefix('tags')->group(function () {
        Route::get('/', [TagController::class, 'index']);
        Route::post('/', [TagController::class, 'store']);
        Route::get('/{tag:slug}', [TagController::class, 'show']);
        Route::put('/{tag:slug}', [TagController::class, 'update']);
        Route::delete('/{tag:slug}', [TagController::class, 'destroy']);
    });
});
