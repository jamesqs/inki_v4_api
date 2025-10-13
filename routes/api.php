<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// public endpoints for basic data and public information
Route::group(['prefix' => 'public/v1'], function () {
    Route::get('/', function (Request $request) {
        return json_encode('echo bázis');
    });

    // Estates Module Routes
    Route::prefix('estate')->group(function () {
        Route::get('/', [App\Modules\Estates\Http\Controllers\EstateController::class, 'index']);
        Route::get('/{Estate}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'show']);
    });

    // Category Module Routes
    Route::prefix('category')->group(function () {
        Route::get('/', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'index']);
        Route::get('/{category}', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'show']);

        Route::get('/{category}/attributes', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'getAttributes']);

    });

    // Locations Module Routes
    Route::prefix('location')->group(function () {
        Route::get('/search', [App\Modules\Locations\Http\Controllers\LocationController::class, 'search']);
        Route::get('/', [App\Modules\Locations\Http\Controllers\LocationController::class, 'index']);
    });

    // Attributes Module Routes
    Route::prefix('attribute')->group(function () {
        Route::get('/', [App\Modules\Attributes\Http\Controllers\AttributeController::class, 'index']);
        Route::get('/{Attribute}', [App\Modules\Attributes\Http\Controllers\AttributeController::class, 'show']);
    });

    // Counties Module Routes
    Route::prefix('county')->group(function () {
        Route::get('/', [App\Modules\Counties\Http\Controllers\CountyController::class, 'index']);
    });

    // Statistics Module Routes
    Route::prefix('statistics')->group(function () {
        Route::post('/pricing-analysis', [App\Modules\Statistics\Http\Controllers\StatisticsController::class, 'getPricingAnalysis']);
        Route::get('/market-trends', [App\Modules\Statistics\Http\Controllers\StatisticsController::class, 'getMarketTrends']);
        Route::get('/price-distribution', [App\Modules\Statistics\Http\Controllers\StatisticsController::class, 'getPriceDistribution']);
        Route::get('/market-insights', [App\Modules\Statistics\Http\Controllers\StatisticsController::class, 'getMarketInsights']);
        Route::get('/available-attributes', [App\Modules\Statistics\Http\Controllers\StatisticsController::class, 'getAvailableAttributes']);
    });

    // Blog Module Routes
    Route::prefix('blog')->group(function () {
        Route::get('/', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'index']);
        Route::get('/{blogPost}', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'show']);
    });

    // News Module Routes
    Route::prefix('news')->group(function () {
        Route::get('/', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'index']);
        Route::get('/{newsArticle}', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'show']);
    });
});

// private endpoints for authenticated users
Route::prefix('private/v1/user')->middleware(['auth:sanctum', 'role:user'])->group(function () {

});

// private endpoints for authenticated company users
Route::prefix('private/v1/company')->middleware(['auth:sanctum', 'role:company-user'])->group(function () {

});

// private endpoints for authenticated admins
Route::prefix('private/v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Attributes Module Routes
    Route::prefix('attribute')->group(function () {
        Route::post('/', [App\Modules\Attributes\Http\Controllers\AttributeController::class, 'store']);
        Route::put('/{attribute}', [App\Modules\Attributes\Http\Controllers\AttributeController::class, 'update']);
        Route::delete('/{attribute}', [App\Modules\Attributes\Http\Controllers\AttributeController::class, 'destroy']);
    });

    // Categories Module Routes
    Route::prefix('category')->group(function () {
        Route::post('/', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'store']);
        Route::put('/{category}', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'update']);
        Route::delete('/{category}', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'destroy']);

        Route::post('/{category}/attributes', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'attachAttributes']);
        Route::put('/{category}/attributes', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'syncAttributes']);
        Route::delete('/{category}/attributes', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'detachAttributes']);
        Route::put('/{category}/attributes/{attribute}', [App\Modules\Categories\Http\Controllers\CategoryController::class, 'updateAttributePivot']);
    });


    // Counties Module Routes
    Route::prefix('county')->group(function () {
        Route::post('/', [App\Modules\Counties\Http\Controllers\CountyController::class, 'store']);
        Route::put('/{county}', [App\Modules\Counties\Http\Controllers\CountyController::class, 'update']);
        Route::delete('/{county}', [App\Modules\Counties\Http\Controllers\CountyController::class, 'destroy']);
    });

    // Companies Module Routes
    Route::prefix('company')->group(function () {
        Route::get('/', [App\Modules\Companies\Http\Controllers\CompanyController::class, 'index']);
        Route::post('/', [App\Modules\Companies\Http\Controllers\CompanyController::class, 'store']);
        Route::get('/{company}', [App\Modules\Companies\Http\Controllers\CompanyController::class, 'show']);
        Route::put('/{company}', [App\Modules\Companies\Http\Controllers\CompanyController::class, 'update']);
        Route::delete('/{company}', [App\Modules\Companies\Http\Controllers\CompanyController::class, 'destroy']);
    });

    // Estates Module Routes
    Route::prefix('estate')->group(function () {
        Route::post('/', [App\Modules\Estates\Http\Controllers\EstateController::class, 'store']);
        Route::put('/{estate}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'update']);
        Route::delete('/{estate}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'destroy']);
    });

    // Location module routes
    Route::prefix('location')->group(function () {
        Route::post('/', [App\Modules\Locations\Http\Controllers\LocationController::class, 'store']);
        Route::put('/{location}', [App\Modules\Locations\Http\Controllers\LocationController::class, 'update']);
        Route::delete('/{location}', [App\Modules\Locations\Http\Controllers\LocationController::class, 'destroy']);
    });

    // Users Module Routes
    Route::prefix('user')->group(function () {
        Route::get('/', [App\Modules\Users\Http\Controllers\UserController::class, 'index']);
        #Route::post('/', [App\Modules\Users\Http\Controllers\UserController::class, 'store']);
        Route::get('/{user}', [App\Modules\Users\Http\Controllers\UserController::class, 'show']);
        #Route::delete('/user', [App\Modules\Users\Http\Controllers\UserController::class, 'destroy']);
        #Route::put('/user', [App\Modules\Users\Http\Controllers\UserController::class, 'update']);
    });

    // Blog Module Routes
    Route::prefix('blog')->group(function () {
        Route::post('/', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'store']);
        Route::put('/{blogPost}', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'update']);
        Route::delete('/{blogPost}', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'destroy']);
    });

    // News Module Routes
    Route::prefix('news')->group(function () {
        Route::post('/', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'store']);
        Route::put('/{newsArticle}', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'update']);
        Route::delete('/{newsArticle}', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'destroy']);
    });
});
