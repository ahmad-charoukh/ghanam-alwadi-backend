<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContentPageController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| المصادقة العامة
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function (): void {
    Route::post(
        '/register',
        [AuthController::class, 'register']
    );

    Route::post(
        '/login',
        [AuthController::class, 'login']
    );

    Route::post(
        '/forgot-password',
        [AuthController::class, 'forgotPassword']
    )->middleware('throttle:3,1');

    Route::post(
        '/reset-password',
        [AuthController::class, 'resetPassword']
    )->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| المسارات المحمية
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->group(function (): void {
        /*
         * المصادقة والتحقق من البريد.
         */
        Route::prefix('auth')
            ->group(function (): void {
                Route::get(
                    '/me',
                    [AuthController::class, 'me']
                );

                Route::post(
                    '/logout',
                    [AuthController::class, 'logout']
                );

                Route::post(
                    '/send-verification-code',
                    [
                        AuthController::class,
                        'sendVerificationCode',
                    ]
                )->middleware('throttle:3,1');

                Route::post(
                    '/verify-email',
                    [AuthController::class, 'verifyEmail']
                )->middleware('throttle:5,1');
            });

        /*
         * الإشعارات.
         */
        Route::prefix('notifications')
            ->group(function (): void {
                Route::get(
                    '/',
                    [NotificationController::class, 'index']
                );

                Route::get(
                    '/unread-count',
                    [
                        NotificationController::class,
                        'unreadCount',
                    ]
                );

                Route::post(
                    '/read-all',
                    [
                        NotificationController::class,
                        'markAllAsRead',
                    ]
                );

                Route::delete(
                    '/clear',
                    [NotificationController::class, 'clear']
                );

                Route::post(
                    '/{notificationId}/read',
                    [
                        NotificationController::class,
                        'markAsRead',
                    ]
                );

                Route::delete(
                    '/{notificationId}',
                    [
                        NotificationController::class,
                        'destroy',
                    ]
                );
            });

        /*
         * سلة المشتريات.
         */
        Route::prefix('cart')
            ->group(function (): void {
                Route::get(
                    '/',
                    [CartController::class, 'index']
                );

                Route::post(
                    '/',
                    [CartController::class, 'store']
                );

                Route::delete(
                    '/clear',
                    [CartController::class, 'clear']
                );

                Route::put(
                    '/{cartItem}',
                    [CartController::class, 'update']
                );

                Route::delete(
                    '/{cartItem}',
                    [CartController::class, 'destroy']
                );
            });

        /*
         * المنتجات المفضلة.
         */
        Route::prefix('favorites')
            ->group(function (): void {
                Route::get(
                    '/',
                    [FavoriteController::class, 'index']
                );

                Route::post(
                    '/',
                    [FavoriteController::class, 'store']
                );

                Route::delete(
                    '/clear',
                    [FavoriteController::class, 'clear']
                );

                Route::get(
                    '/{product}/check',
                    [FavoriteController::class, 'check']
                );

                Route::delete(
                    '/{product}',
                    [FavoriteController::class, 'destroy']
                );
            });

        /*
         * تقييمات العميل.
         */
        Route::prefix('reviews')
            ->group(function (): void {
                Route::get(
                    '/',
                    [ReviewController::class, 'mine']
                );

                Route::post(
                    '/',
                    [ReviewController::class, 'store']
                );

                Route::put(
                    '/{review}',
                    [ReviewController::class, 'update']
                );

                Route::delete(
                    '/{review}',
                    [ReviewController::class, 'destroy']
                );
            });

        /*
         * عناوين التوصيل.
         */
        Route::prefix('addresses')
            ->group(function (): void {
                Route::get(
                    '/',
                    [AddressController::class, 'index']
                );

                Route::post(
                    '/',
                    [AddressController::class, 'store']
                );

                Route::post(
                    '/{address}/default',
                    [AddressController::class, 'setDefault']
                );

                Route::get(
                    '/{address}',
                    [AddressController::class, 'show']
                );

                Route::put(
                    '/{address}',
                    [AddressController::class, 'update']
                );

                Route::delete(
                    '/{address}',
                    [AddressController::class, 'destroy']
                );
            });

        /*
         * طلبات العميل.
         */
        Route::prefix('orders')
            ->group(function (): void {
                Route::get(
                    '/',
                    [OrderController::class, 'index']
                );

                Route::post(
                    '/preview',
                    [OrderController::class, 'preview']
                );

                Route::post(
                    '/',
                    [OrderController::class, 'store']
                );

                Route::post(
                    '/{orderNumber}/cancel',
                    [OrderController::class, 'cancel']
                );

                Route::get(
                    '/{orderNumber}',
                    [OrderController::class, 'show']
                );
            });

        /*
         * حساب العميل.
         */
        Route::prefix('account')
            ->group(function (): void {
                Route::put(
                    '/profile',
                    [ProfileController::class, 'update']
                );

                Route::put(
                    '/password',
                    [
                        ProfileController::class,
                        'updatePassword',
                    ]
                );

                Route::delete(
                    '/',
                    [
                        ProfileController::class,
                        'deleteAccount',
                    ]
                )->middleware('throttle:3,1');

                Route::get(
                    '/support-tickets',
                    [SupportTicketController::class, 'index']
                );

                Route::post(
                    '/support-tickets',
                    [SupportTicketController::class, 'store']
                );

                Route::get(
                    '/support-tickets/{ticketNumber}',
                    [SupportTicketController::class, 'show']
                );

                Route::post(
                    '/support-tickets/{ticketNumber}/reply',
                    [
                        SupportTicketController::class,
                        'replyAuthenticated',
                    ]
                );
            });
    });

/*
|--------------------------------------------------------------------------
| الصفحة الرئيسية والإعدادات
|--------------------------------------------------------------------------
*/

Route::get(
    '/home',
    [HomeController::class, 'index']
);

Route::get(
    '/settings',
    [SettingController::class, 'index']
);

/*
|--------------------------------------------------------------------------
| الأسئلة الشائعة
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'faqs',
    FaqController::class
)->only([
    'index',
    'show',
]);

/*
|--------------------------------------------------------------------------
| الصفحات التعريفية والقانونية
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'content-pages',
    ContentPageController::class
)->only([
    'index',
    'show',
]);

/*
|--------------------------------------------------------------------------
| التصنيفات
|--------------------------------------------------------------------------
*/

Route::apiResource(
    'categories',
    CategoryController::class
)->only([
    'index',
    'show',
]);

/*
|--------------------------------------------------------------------------
| المنتجات والتقييمات العامة
|--------------------------------------------------------------------------
*/

Route::get(
    '/products/{product}/reviews',
    [ReviewController::class, 'index']
);

Route::apiResource(
    'products',
    ProductController::class
)->only([
    'index',
    'show',
]);

/*
|--------------------------------------------------------------------------
| تذاكر الدعم العامة
|--------------------------------------------------------------------------
*/

Route::post(
    '/support-tickets',
    [SupportTicketController::class, 'store']
);

Route::get(
    '/support-tickets/{ticketNumber}/track',
    [SupportTicketController::class, 'track']
);

Route::post(
    '/support-tickets/{ticketNumber}/reply',
    [SupportTicketController::class, 'reply']
);