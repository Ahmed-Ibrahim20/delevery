<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\AuthController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    // Authentication Routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        /**
         * Dashboard Routes (With Permission Middleware)
         */
        Route::prefix('dashboard')
            ->group(function () {

                //  User Management Routes (CRUD API)
                /*  | HTTP Method | Endpoint             | Action  | Controller Method |
                        | ----------- | -------------------- | ------- | ----------------- |
                        | GET         | `/api/v1/users`      | Index   | `index()`         |
                        | GET         | `/api/v1/users/{id}` | Show    | `show()`          |
                        | POST        | `/api/v1/users`      | Store   | `store()`         |
                        | PUT/PATCH   | `/api/v1/users/{id}` | Update  | `update()`        |
                        | DELETE      | `/api/v1/users/{id}` | Destroy | `destroy()`       | */

                Route::apiResource('users', UserController::class);

                // Additional User Management Routes
                Route::put('users/{id}/approve', [UserController::class, 'approve']);
                Route::put('users/{id}/change-password', [UserController::class, 'changePassword']);
                Route::put('users/{id}/change-active-status', [UserController::class, 'changeActiveStatus']);
                Route::put('users/{id}/change-commission', [UserController::class, 'changeCommissionPercentage']);
                Route::put('users/{id}/change-availability-status', [UserController::class, 'changeAvailabilityStatus']);
                Route::put('users/toggle-my-availability', [UserController::class, 'toggleMyAvailability']);
                Route::get('users/available-drivers', [UserController::class, 'getAvailableDrivers']);

                // Notifications Management Routes
                /*  | HTTP Method | Endpoint                                    | Action                    | Controller Method       |
                    | ----------- | ------------------------------------------- | ------------------------- | ----------------------- |
                    | GET         | `/api/v1/notifications`                     | User Notifications        | `index()`               |
                    | GET         | `/api/v1/notifications/unread`              | Unread Notifications      | `unread()`              |
                    | GET         | `/api/v1/notifications/stats`               | Notification Stats        | `stats()`               |
                    | GET         | `/api/v1/notifications/{id}`                | Show Notification         | `show()`                |
                    | PUT         | `/api/v1/notifications/{id}/read`           | Mark as Read              | `markAsRead()`          |
                    | PUT         | `/api/v1/notifications/{id}/toggle`         | Toggle Read Status        | `toggleRead()`          |
                    | PUT         | `/api/v1/notifications/mark-all-read`       | Mark All as Read          | `markAllAsRead()`       |
                    | DELETE      | `/api/v1/notifications/{id}`                | Delete Notification       | `destroy()`             |
                    | POST        | `/api/v1/notifications/create`              | Create Notification       | `create()`              |
                    | GET         | `/api/v1/notifications/role/{role}`         | Role Notifications        | `roleNotifications()`   | */

                Route::prefix('notifications')->group(function () {
                    Route::get('/', [NotificationController::class, 'index']);
                    Route::get('unread', [NotificationController::class, 'unread']);
                    Route::get('stats', [NotificationController::class, 'stats']);
                    Route::get('{id}', [NotificationController::class, 'show']);
                    Route::put('{id}/read', [NotificationController::class, 'markAsRead']);
                    Route::put('{id}/toggle', [NotificationController::class, 'toggleRead']);
                    Route::put('mark-all-read', [NotificationController::class, 'markAllAsRead']);
                    Route::delete('{id}', [NotificationController::class, 'destroy']);
                    Route::post('create', [NotificationController::class, 'create']);
                    Route::get('role/{role}', [NotificationController::class, 'roleNotifications']);
                });

                //  Order Management Routes (CRUD API)
                /*  | HTTP Method | Endpoint               | Action  | Controller Method |
                    | ----------- | ---------------------- | ------- | ----------------- |
                    | GET         | `/api/v1/orders`       | Index   | `index()`         |
                    | GET         | `/api/v1/orders/{id}`  | Show    | `show()`          |
                    | POST        | `/api/v1/orders`       | Store   | `store()`         |
                    | PUT/PATCH   | `/api/v1/orders/{id}`  | Update  | `update()`        |
                    | DELETE      | `/api/v1/orders/{id}`  | Destroy | `destroy()`       | */
                Route::apiResource('orders', OrderController::class);

                // Additional Order Management Routes
                Route::put('orders/{id}/change-status', [OrderController::class, 'changeStatus']);
                Route::get('orders/active', [OrderController::class, 'getActiveOrders']);

                //  Complaint Management Routes (CRUD API)
                /*  | HTTP Method | Endpoint                    | Action  | Controller Method |
                    | ----------- | --------------------------- | ------- | ----------------- |
                    | GET         | `/api/v1/complaints`        | Index   | `index()`         |
                    | GET         | `/api/v1/complaints/{id}`   | Show    | `show()`          |
                    | POST        | `/api/v1/complaints`        | Store   | `store()`         |
                    | PUT/PATCH   | `/api/v1/complaints/{id}`   | Update  | `update()`        |
                    | DELETE      | `/api/v1/complaints/{id}`   | Destroy | `destroy()`       | */
                Route::apiResource('complaints', ComplaintController::class);
                Route::apiResource('complaints', ComplaintController::class);

                // Additional Complaint Management Routes
                Route::put('complaints/{id}/change-status', [ComplaintController::class, 'changeStatus']);

                // Reports Management Routes
                /*  | HTTP Method | Endpoint                                    | Action               | Controller Method       |
                    | ----------- | ------------------------------------------- | -------------------- | ----------------------- |
                    | GET         | `/api/v1/reports/admin`                     | Admin Report         | `adminReport()`         |
                    | GET         | `/api/v1/reports/comprehensive`             | Comprehensive Report | `comprehensiveReport()` |
                    | GET         | `/api/v1/reports/delivery/{id}`             | Delivery Report      | `deliveryReport()`      |
                    | GET         | `/api/v1/reports/shop/{id}`                 | Shop Report          | `shopReport()`          |
                    | GET         | `/api/v1/reports/my-delivery`               | My Delivery Report   | `myDeliveryReport()`    |
                    | GET         | `/api/v1/reports/my-shop`                   | My Shop Report       | `myShopReport()`        | */

                Route::prefix('reports')->group(function () {
                    Route::get('admin', [ReportController::class, 'adminReport']);
                    Route::get('comprehensive', [ReportController::class, 'comprehensiveReport']);
                    Route::get('delivery/{id}', [ReportController::class, 'deliveryReport']);
                    Route::get('shop/{id}', [ReportController::class, 'shopReport']);
                    Route::get('my-delivery', [ReportController::class, 'myDeliveryReport']);
                    Route::get('my-shop', [ReportController::class, 'myShopReport']);
                });
            });
    });
});
