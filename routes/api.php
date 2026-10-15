<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AbsensiSiswaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DivisiController;
use App\Http\Controllers\Api\PertemuanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => true, 'message' => 'healthy']));

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api-limit'])->group(function () {

    // semua role
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    

    // siswa + pengurus (ikut absen)
    Route::middleware('role:siswa,pengurus')->group(function () {
        Route::get('/absensi/resolve', [AbsensiSiswaController::class, 'resolve'])->middleware('throttle:resolve');
        Route::post('/absensi/{pertemuan}/masuk', [AbsensiSiswaController::class, 'masuk']);
        Route::get('/absensi/riwayat', [AbsensiSiswaController::class, 'riwayat']);
    });

    // index saja: admin, pengurus, guru
    Route::middleware('role:admin,pengurus,guru')->group(function () {
        Route::get('/user', [UserController::class, 'index']);
        Route::get('/pertemuan', [PertemuanController::class, 'index']);
        Route::get('/absensi', [AbsensiController::class, 'index']);
        Route::get('/divisi', [DivisiController::class, 'index']);
    });
    
    // admin + pengurus
    Route::middleware('role:admin,pengurus')->group(function () {
        Route::apiResource('user', UserController::class)->except(['index']);
    
        Route::get('/pertemuan/{pertemuan}', [PertemuanController::class, 'show']);
        Route::post('/pertemuan', [PertemuanController::class, 'store']);
        Route::patch('/pertemuan/{pertemuan}', [PertemuanController::class, 'update']);
        Route::delete('/pertemuan/{pertemuan}', [PertemuanController::class, 'destroy']); // pengurus: hanya miliknya
    
        Route::get('/absensi/detail', [AbsensiController::class, 'detail']);
        Route::patch('/absensi/{absensi}', [AbsensiController::class, 'update']);
    });
    
    // admin saja
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('divisi', DivisiController::class)->except(['index']);
        Route::delete('/absensi/{absensi}', [AbsensiController::class, 'destroy']);
    });
});