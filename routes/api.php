<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LinkController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')
    ->name('api.logout');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/links', [LinkController::class, 'store'])->name('api.links.store');
    Route::get('/links', [LinkController::class, 'index'])->name('api.links.index');
    Route::patch('/links/{link}/deactivate', [LinkController::class, 'deactivate'])
        ->name('api.links.deactivate');
});
