<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\Api\DomainOrderController;
use App\Http\Controllers\api\DomainTldController;
use App\Http\Controllers\api\TestApiController;
// use Illuminate\Http\Request;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Public route
// Route::post('/customer/login',  [CustomerAuthController::class, 'login']);
// Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
// Route::apiResource('domains', DomainTldController::class);
// Route::apiResource('domain-orders', DomainOrderController::class);
// Protected routes


Route::middleware('auth:api')->group(function () {
    Route::get('/users', [UserController::class,'index']); 
    Route::post('/users', [UserController::class,'store']); 
    Route::get('/users/{id}', [UserController::class,'show']); 
    Route::put('/users/{id}', [UserController::class,'update']);
    Route::delete('/users/{id}', [UserController::class,'destroy']);
});


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);    
    });
});

Route::prefix('customer/auth')->group(function () {
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('login', [CustomerAuthController::class, 'login']);
    Route::middleware('auth:customer_api')->group(function () {
        Route::post('refresh', [CustomerAuthController::class, 'refresh']);
        Route::get('profile', [CustomerAuthController::class, 'profile']);
        Route::post('logout', [CustomerAuthController::class, 'logout']);    
    });
});

Route::middleware('auth:customer_api')->group(function () {
    Route::patch('customers/{id}/update-basic',  [CustomerController::class, 'updateBasic']);
    Route::patch('customers/{id}/update-info',   [CustomerController::class, 'updateInfo']);
    Route::patch('customers/{id}/update-password',[CustomerController::class, 'updatePassword']);
    Route::patch('customers/{id}/status', [CustomerController::class, 'updateStatus']);
    Route::apiResource('customers', CustomerController::class);
});

Route::apiResource('domains', DomainTldController::class);
Route::apiResource('domain-orders', DomainOrderController::class);