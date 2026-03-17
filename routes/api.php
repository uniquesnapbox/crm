<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Integration\AttendanceController;
use App\Http\Controllers\Api\Integration\ClientController;
use App\Http\Controllers\Api\Integration\EmployeeController;
use App\Http\Controllers\Api\Integration\LeadController;
use App\Http\Controllers\Api\Integration\ProjectController;
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
    Route::get('clients', [ClientController::class, 'index']);
    Route::get('clients/{client}', [ClientController::class, 'show']);
    Route::get('leads', [LeadController::class, 'index']);
    Route::get('leads/{lead}', [LeadController::class, 'show']);
    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::get('attendance/{attendance}', [AttendanceController::class, 'show']);
});
