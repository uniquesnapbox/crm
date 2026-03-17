<?php

use App\Http\Controllers\Api\AttendanceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

ApiRoute::group(['namespace' => 'App\Http\Controllers'], function () {
    ApiRoute::get('purchased-module', ['as' => 'api.purchasedModule', 'uses' => 'HomeController@installedModule']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/attendance/clock-in', [AttendanceApiController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceApiController::class, 'clockOut']);
    Route::post('/attendance/location-update', [AttendanceApiController::class, 'locationUpdate']);
});
