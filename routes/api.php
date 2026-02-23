<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\UserDashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\VesselTypeController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminTripController;
use App\Http\Controllers\Admin\AdminVesselController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminOfferController;
use App\Http\Controllers\Admin\AdminSettingController;
use Illuminate\Support\Facades\Route;

// صفحة الجذر للـ API (عند فتح /api في المتصفح)
Route::get('/', function () {
    return response()->json([
        'message' => 'مرحباً، API مركب يعمل',
        'endpoints' => [
            'trips' => '/api/trips',
            'locations' => '/api/locations',
            'vessel-types' => '/api/vessel-types',
            'auth' => '/api/auth/login',
        ],
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::get('user/dashboard', [UserDashboardController::class, 'index']);
    Route::put('user/profile', [UserDashboardController::class, 'updateProfile']);
    Route::post('user/change-password', [UserDashboardController::class, 'changePassword']);

    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::put('bookings/{booking}', [BookingController::class, 'update']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
    });

    // Support Tickets (User)
    Route::prefix('support')->group(function () {
        Route::get('/', [SupportController::class, 'index']);
        Route::post('/', [SupportController::class, 'store']);
        Route::get('unread-count', [SupportController::class, 'unreadCount']);
        Route::get('{ticket}', [SupportController::class, 'show']);
        Route::post('{ticket}/message', [SupportController::class, 'sendMessage']);
        Route::post('{ticket}/close', [SupportController::class, 'close']);
    });

    // Reviews (User)
    Route::post('reviews', [ReviewController::class, 'store']);
    Route::post('reviews/{review}/reply', [ReviewController::class, 'reply']);
});

Route::get('home', [HomeController::class, 'index']);
Route::get('offers', [OfferController::class, 'index']);
Route::get('vessel-types', [VesselTypeController::class, 'index']);
Route::get('locations', [LocationController::class, 'index']);
Route::get('locations/{location}', [LocationController::class, 'show']);
Route::get('trips', [TripController::class, 'index']);
Route::get('trips/{trip}', [TripController::class, 'show']);
Route::get('trips/{trip}/reviews', [ReviewController::class, 'index']);

// Public settings (only public fields)
Route::get('settings', function () {
    $publicKeys = ['site_name', 'site_name_en', 'site_description', 'contact_email', 'contact_phone', 'contact_address'];
    $settings = \App\Models\Setting::whereIn('key', $publicKeys)->get()->mapWithKeys(function ($setting) {
        return [$setting->key => $setting->value];
    });
    return response()->json($settings);
});

// Public policies
Route::get('policies', [AdminSettingController::class, 'policies']);
Route::get('policies/{slug}', function ($slug) {
    $policy = \App\Models\Policy::where('slug', $slug)->where('is_active', true)->first();
    return $policy ? response()->json($policy) : response()->json(['message' => 'غير موجود'], 404);
});

Route::prefix('admin')->middleware(['auth:sanctum', \App\Http\Middleware\EnsureUserIsAdmin::class])->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index']);

    Route::apiResource('offers', AdminOfferController::class);
    Route::post('offers/{offer}/trips', [AdminOfferController::class, 'syncTrips']);

    Route::apiResource('trips', AdminTripController::class)->except(['show']);
    Route::get('trips/{trip}/images', [AdminTripController::class, 'getImages']);
    Route::post('trips/{trip}/cover', [AdminTripController::class, 'uploadCover']);
    Route::post('trips/{trip}/gallery', [AdminTripController::class, 'uploadGallery']);
    Route::delete('trips/{trip}/images/{tripImage}', [AdminTripController::class, 'deleteImage']);
    Route::get('bookings', [AdminBookingController::class, 'index']);
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);
    Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel']);

    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payments/stats', [AdminPaymentController::class, 'stats']);
    Route::get('payments/{payment}', [AdminPaymentController::class, 'show']);

    Route::get('vessel-types', [AdminVesselController::class, 'vesselTypes']);
    Route::post('vessel-types', [AdminVesselController::class, 'storeVesselType']);
    Route::put('vessel-types/{vesselType}', [AdminVesselController::class, 'updateVesselType']);

    Route::get('vessels', [AdminVesselController::class, 'vessels']);
    Route::post('vessels', [AdminVesselController::class, 'storeVessel']);
    Route::put('vessels/{vessel}', [AdminVesselController::class, 'updateVessel']);
    Route::get('vessels/{vessel}/images', [AdminVesselController::class, 'getImages']);
    Route::post('vessels/{vessel}/cover', [AdminVesselController::class, 'uploadCover']);
    Route::post('vessels/{vessel}/gallery', [AdminVesselController::class, 'uploadGallery']);
    Route::delete('vessels/{vessel}/images/{vesselImage}', [AdminVesselController::class, 'deleteImage']);

    Route::get('locations', [AdminLocationController::class, 'index']);
    Route::post('locations', [AdminLocationController::class, 'store']);
    Route::put('locations/{location}', [AdminLocationController::class, 'update']);
    Route::delete('locations/{location}', [AdminLocationController::class, 'destroy']);
    Route::post('locations/{location}/images', [AdminLocationController::class, 'uploadImages']);
    Route::delete('locations/{location}/images/{location_image}', [AdminLocationController::class, 'deleteImage']);
    Route::post('locations/{location}/images/{location_image}/primary', [AdminLocationController::class, 'setPrimaryImage']);

    // Users Management
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::get('users/{user}', [AdminUserController::class, 'show']);
    Route::put('users/{user}', [AdminUserController::class, 'update']);
    Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
    Route::get('login-logs', [AdminUserController::class, 'loginLogs']);

    // Support Tickets (Admin)
    Route::get('support', [AdminSupportController::class, 'index']);
    Route::get('support/stats', [AdminSupportController::class, 'stats']);
    Route::get('support/{ticket}', [AdminSupportController::class, 'show']);
    Route::post('support/{ticket}/assign', [AdminSupportController::class, 'assign']);
    Route::post('support/{ticket}/status', [AdminSupportController::class, 'updateStatus']);
    Route::post('support/{ticket}/message', [AdminSupportController::class, 'sendMessage']);

    // Reviews
    Route::get('reviews', [AdminReviewController::class, 'index']);
    Route::get('reviews/stats', [AdminReviewController::class, 'stats']);
    Route::post('reviews/{review}/approve', [AdminReviewController::class, 'approve']);
    Route::post('reviews/{review}/reject', [AdminReviewController::class, 'reject']);
    Route::post('reviews/{review}/featured', [AdminReviewController::class, 'toggleFeatured']);
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);

    // Settings & Policies
    Route::get('settings', [AdminSettingController::class, 'index']);
    Route::post('settings', [AdminSettingController::class, 'update']);
    Route::post('settings/clear-cache', [AdminSettingController::class, 'clearCache']);
    Route::get('settings/health', [AdminSettingController::class, 'systemHealth']);
    Route::post('settings/backup', [AdminSettingController::class, 'backupDatabase']);
    Route::post('settings/optimize-db', [AdminSettingController::class, 'optimizeDatabase']);
    Route::get('settings/logs', [AdminSettingController::class, 'getLogs']);
    Route::delete('settings/logs', [AdminSettingController::class, 'clearLogs']);
    Route::post('settings/test-email', [AdminSettingController::class, 'testEmail']);
    Route::get('settings/db-stats', [AdminSettingController::class, 'databaseStats']);
    Route::get('settings/backups', [AdminSettingController::class, 'listBackups']);
    Route::get('settings/backups/{filename}', [AdminSettingController::class, 'downloadBackup']);
    Route::delete('settings/backups/{filename}', [AdminSettingController::class, 'deleteBackup']);
    Route::post('policies', [AdminSettingController::class, 'storePolicy']);
    Route::put('policies/{policy}', [AdminSettingController::class, 'updatePolicy']);
    Route::delete('policies/{policy}', [AdminSettingController::class, 'deletePolicy']);
});
