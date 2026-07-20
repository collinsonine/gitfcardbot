<?php

use App\Http\Controllers\Api\ShadowListenerController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/whatsapp/webhook', [WebhookController::class, 'handle'])->name('api.whatsapp.webhook');
Route::post('/whatsapp/listener', [ShadowListenerController::class, 'handle'])->name('api.whatsapp.listener');
