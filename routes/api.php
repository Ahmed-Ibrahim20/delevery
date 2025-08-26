<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;

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

                //  Order Management Routes (CRUD API)
                /*  | HTTP Method | Endpoint               | Action  | Controller Method |
                    | ----------- | ---------------------- | ------- | ----------------- |
                    | GET         | `/api/v1/orders`       | Index   | `index()`         |
                    | GET         | `/api/v1/orders/{id}`  | Show    | `show()`          |
                    | POST        | `/api/v1/orders`       | Store   | `store()`         |
                    | PUT/PATCH   | `/api/v1/orders/{id}`  | Update  | `update()`        |
                    | DELETE      | `/api/v1/orders/{id}`  | Destroy | `destroy()`       | */

                Route::apiResource('orders', OrderController::class);
            });
    });
});
