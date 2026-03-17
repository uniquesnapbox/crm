<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Integration\EmployeeController;
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

Route::post('login', [AuthController::class, 'login'])->name('api.login');

Route::prefix('integrations')->middleware('integration.token')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::get('employees/{employee}', [EmployeeController::class, 'show']);
});
