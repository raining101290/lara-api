<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\api\CustomerController;
use App\Http\Controllers\Api\DomainOrderController;
use App\Http\Controllers\api\DomainTldController;
use App\Http\Controllers\api\TestApiController;
// use Illuminate\Http\Request;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:api')->group(function () {
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);    
    });
});
Route::middleware('auth:api')->group(function () {
    Route::get('/users', [UserController::class,'index']); 
    Route::post('/users', [UserController::class,'store']); 
    Route::get('/users/{id}', [UserController::class,'show']); 
    Route::put('/users/{id}', [UserController::class,'update']);
    Route::delete('/users/{id}', [UserController::class,'destroy']);
    
    Route::apiResource('customers', CustomerController::class);
});
Route::middleware('auth:api')->group(function () {
    Route::apiResource('domain-orders', DomainOrderController::class);
});


// CUSTOMER SELF-SERVICE APIs
Route::middleware('auth:customer_api')->group(function () {
    // Customer profile management
    Route::get('profile/dashboard-summary', [CustomerController::class, 'dashboardSummary']);
    Route::get('profile/{id}', [CustomerController::class, 'show']);
    Route::patch('profile/{id}/update-basic', [CustomerController::class, 'updateBasic']);
    Route::patch('profile/{id}/update-info', [CustomerController::class, 'updateInfo']);
    Route::patch('profile/{id}/update-password', [CustomerController::class, 'updatePassword']);

    // Customer invoice routes
    Route::get('my-invoices', [InvoiceController::class, 'myInvoices']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show']);
    Route::get('invoices/{id}/download', [InvoiceController::class, 'downloadPdf']);
});
Route::middleware('auth:customer_api')->group(function () {
    Route::apiResource('orders', DomainOrderController::class);
});
Route::apiResource('domains', DomainTldController::class);


Route::get('/debug-auth', function () {
    return response()->json([
        'api_user' => auth('api')->user(),
        'customer_user' => auth('customer_api')->user(),
    ]);
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

// Route::middleware(['auth:customer_api', 'auth:api'])->group(function () {
//     Route::patch('customers/{id}/update-basic',  [CustomerController::class, 'updateBasic']);
//     Route::patch('customers/{id}/update-info',   [CustomerController::class, 'updateInfo']);
//     Route::patch('customers/{id}/update-password',[CustomerController::class, 'updatePassword']);
//     Route::patch('customers/{id}/status', [CustomerController::class, 'updateStatus']);
//     Route::apiResource('customers', CustomerController::class);
//     Route::apiResource('domain-orders', DomainOrderController::class);
//     Route::get('customers/dashboard-summary', [CustomerController::class, 'dashboardSummary']);
// });

// Route::apiResource('domains', DomainTldController::class);

// Route::middleware('auth:customer_api')->group(function () {
//     Route::get('my-invoices', [InvoiceController::class, 'myInvoices']);

//     Route::get('invoices/{id}/download', [InvoiceController::class, 'downloadPdf']);
//     Route::get('invoices/{id}', [InvoiceController::class, 'show']);
// });

// Route::middleware('auth:admin_api')->group(function () {
//     Route::get('invoices', action: [InvoiceController::class, 'index']);
//     Route::patch('invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid']);
// });