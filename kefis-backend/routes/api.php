<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockBalanceController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\ProductCompanyController;

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('login',    [AuthController::class, 'login']);
    

    Route::middleware('auth:api')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::apiResource('branches', BranchController::class);
      Route::apiResource('products', ProductController::class);
      Route::apiResource('stock-balances', StockBalanceController::class)->only(['index', 'show']);
      Route::apiResource('purchases',      PurchaseController::class)->except(['update']);
       Route::apiResource('sales',          SaleController::class)->except(['update']);
       Route::apiResource('transfers',      TransferController::class)->except(['update']);
});

 
// Product companies (admin manages companies per product)
Route::get('/products/{productId}/companies', [ProductCompanyController::class, 'index']);
Route::post('/products/{productId}/companies', [ProductCompanyController::class, 'store']);
Route::put('/products/{productId}/companies/{companyId}', [ProductCompanyController::class, 'update']);
Route::delete('/products/{productId}/companies/{companyId}', [ProductCompanyController::class, 'destroy']);
