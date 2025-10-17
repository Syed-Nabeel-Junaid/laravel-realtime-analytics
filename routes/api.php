<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController; 
use App\Http\Controllers\OrderAnalyticsController;
use App\Http\Controllers\DashboardController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/active', [OrderController::class, 'getActiveOrders']);
Route::get('/orders/popular-dishes', [OrderAnalyticsController::class, 'popularDishes']);
Route::get('/dashboard', [DashboardController::class, 'getDashboard']);
Route::get('/analytics/delivery-times', [OrderAnalyticsController::class, 'deliveryTimes']);
Route::get('/analytics/peak-hours', [OrderAnalyticsController::class, 'peakHours']);

