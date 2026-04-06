<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\LeadContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileAttendanceController;
use App\Http\Controllers\Api\AttendanceAdminController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\LoginController;

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
Route::post('whatsapp/send-otp', [LoginController::class, 'sendWhatsappOtp'])->name('api.whatsapp.send_otp');
Route::post('whatsapp/verify-otp', [LoginController::class, 'verifyWhatsappOtp'])->name('api.whatsapp.verify_otp');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'profile'])->name('api.profile');
    Route::get('lead-contacts', [LeadContactController::class, 'apiIndex'])->name('api.lead-contacts.index');
    Route::post('lead-contacts', [LeadContactController::class, 'store'])->name('api.lead-contacts.store');
    Route::patch('lead-contacts/{id}', [LeadContactController::class, 'update'])->name('api.lead-contacts.update');

    // Mobile attendance + live tracking
    Route::post('attendance/clock-in', [MobileAttendanceController::class, 'clockIn'])->name('api.attendance.clock-in');
    Route::post('attendance/clock-out', [MobileAttendanceController::class, 'clockOut'])->name('api.attendance.clock-out');
    Route::post('attendance/location-update', [MobileAttendanceController::class, 'liveUpdate'])->name('api.attendance.location-update');
    Route::post('attendance/location-alert', [MobileAttendanceController::class, 'locationAlert'])->name('api.attendance.location-alert');

    // Admin attendance list
    Route::get('attendance', [AttendanceAdminController::class, 'index'])->name('api.attendance.index');

    // Dashboard stats
    Route::get('dashboard/stats', [DashboardApiController::class, 'stats'])->name('api.dashboard.stats');
});
