<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // CRUD Master Data
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('customers', CustomerController::class);

    // API Transaksi
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::patch('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);
    
    // API Mobile Customer
    Route::get('/status-laundry', [TransactionController::class, 'customerStatus']);

    // Reporting API (Laporan Pendapatan & Statistik)
    Route::get('/reports/income', [ReportController::class, 'income']);
    Route::get('/reports/statistics', [ReportController::class, 'statistics']);
});