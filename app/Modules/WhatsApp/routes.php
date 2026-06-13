<?php

use App\Modules\WhatsApp\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth', 'prefix' => 'account/whatsapp'], function () {
    Route::post('/send-message', [WhatsAppController::class, 'send'])->name('whatsapp.send-message');
});

