<?php

use App\Http\Controllers\Api\HoldController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Product Routes
Route::get('/products/{id}', [ProductController::class, 'show']);

// Hold Routes
Route::post('/holds', [HoldController::class, 'store']);

// Order Routes
Route::post('/orders', [OrderController::class, 'store']);

// Webhook Routes
Route::post('/payments/webhook', [WebhookController::class, 'handlePayment']);
