<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// User Profile Routes
Route::put('/user/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('/user/profile-picture', [AuthController::class, 'uploadProfilePicture'])->middleware('auth:sanctum');
Route::delete('/user/profile-picture', [AuthController::class, 'deleteProfilePicture'])->middleware('auth:sanctum');

// Social Authentication Routes
Route::post('/auth/google', [AuthController::class, 'googleAuth']);
Route::post('/auth/facebook', [AuthController::class, 'facebookAuth']);

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

    // Media Module Routes
    Route::prefix('media')->group(function () {
        Route::get('/', [App\Modules\Media\Http\Controllers\MediaController::class, 'index']);
        Route::get('/{media}', [App\Modules\Media\Http\Controllers\MediaController::class, 'show']);
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
    // Estates - users can create and manage their own properties
    Route::prefix('estate')->group(function () {
        Route::post('/', [App\Modules\Estates\Http\Controllers\EstateController::class, 'store']);
        Route::get('/my', [App\Modules\Estates\Http\Controllers\EstateController::class, 'myEstates']);
        Route::get('/{id}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'showForUser']);
        Route::put('/{estate}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'update']);
        Route::delete('/{estate}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'destroy']);

        // Status workflow
        Route::post('/{estate}/submit', [App\Modules\Estates\Http\Controllers\EstateController::class, 'submitForReview']);
        Route::post('/{estate}/cancel-review', [App\Modules\Estates\Http\Controllers\EstateController::class, 'cancelReview']);

        // Photo management
        Route::post('/{estate}/photos', [App\Modules\Estates\Http\Controllers\EstateController::class, 'uploadPhotos']);
        Route::delete('/{estate}/photos/{photoId}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'deletePhoto']);
        Route::put('/{estate}/photos/reorder', [App\Modules\Estates\Http\Controllers\EstateController::class, 'reorderPhotos']);
    });

    // Messages - users can communicate about estates
    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [App\Modules\Messages\Http\Controllers\MessageController::class, 'getConversations']);
        Route::get('/unread-count', [App\Modules\Messages\Http\Controllers\MessageController::class, 'getUnreadCount']);
        Route::get('/estate/{estateId}', [App\Modules\Messages\Http\Controllers\MessageController::class, 'getMessagesForEstate']);
        Route::get('/estate/{estateId}/user/{userId}', [App\Modules\Messages\Http\Controllers\MessageController::class, 'getConversationWith']);
        Route::post('/send', [App\Modules\Messages\Http\Controllers\MessageController::class, 'sendMessage']);
        Route::put('/{messageId}/read', [App\Modules\Messages\Http\Controllers\MessageController::class, 'markAsRead']);
        Route::put('/estate/{estateId}/user/{senderId}/read', [App\Modules\Messages\Http\Controllers\MessageController::class, 'markConversationAsRead']);
        Route::delete('/{messageId}', [App\Modules\Messages\Http\Controllers\MessageController::class, 'deleteMessage']);
    });

    // Favorites - users can save properties to their favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{estateId}', [FavoriteController::class, 'destroy']);
        Route::get('/check/{estateId}', [FavoriteController::class, 'check']);
    });
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

        // Admin workflow routes
        Route::get('/', [App\Modules\Estates\Http\Controllers\EstateController::class, 'adminIndex']);
        Route::get('/pending-review', [App\Modules\Estates\Http\Controllers\EstateController::class, 'getPendingReview']);
        Route::get('/{id}', [App\Modules\Estates\Http\Controllers\EstateController::class, 'showForAdmin']);
        Route::post('/{estate}/approve', [App\Modules\Estates\Http\Controllers\EstateController::class, 'approve']);
        Route::post('/{estate}/reject', [App\Modules\Estates\Http\Controllers\EstateController::class, 'reject']);
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

        // Attach/Detach media
        Route::post('/{blogPost}/media', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'attachMedia']);
        Route::delete('/{blogPost}/media', [App\Modules\Blog\Http\Controllers\BlogPostController::class, 'detachMedia']);
    });

    // News Module Routes
    Route::prefix('news')->group(function () {
        Route::post('/', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'store']);
        Route::put('/{newsArticle}', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'update']);
        Route::delete('/{newsArticle}', [App\Modules\News\Http\Controllers\NewsArticleController::class, 'destroy']);
    });

    // Media Module Routes (Admin)
    Route::prefix('media')->group(function () {
        Route::post('/upload', [App\Modules\Media\Http\Controllers\MediaController::class, 'upload']);
        Route::put('/{media}', [App\Modules\Media\Http\Controllers\MediaController::class, 'update']);
        Route::delete('/{media}', [App\Modules\Media\Http\Controllers\MediaController::class, 'destroy']);
        Route::delete('/{media}/force', [App\Modules\Media\Http\Controllers\MediaController::class, 'forceDestroy']);
    });
});
