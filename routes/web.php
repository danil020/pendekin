<?php

use App\Http\Controllers\LinkRedirectController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Predis\Connection\ConnectionException as PredisConnectionException;

Route::get('/health', function () {
    $databaseConnected = true;

    try {
        DB::connection()->getPdo();
    } catch (PDOException) {
        $databaseConnected = false;
    }

    $cacheStatus = 'connected';

    try {
        Cache::get('health:check');
    } catch (PredisConnectionException| PDOException) {
        $cacheStatus = 'disconnected';
    }

    return response()->json([
        'status' => $databaseConnected ? 'ok' : 'error',
        'database' => $databaseConnected ? 'connected' : 'disconnected',
        'cache' => $cacheStatus,
    ], $databaseConnected ? 200 : 503);
})->withoutMiddleware([
    PreventRequestForgery::class,
    StartSession::class,
    ShareErrorsFromSession::class,
])->name('health');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{code}', LinkRedirectController::class)
    ->where('code', '[A-Za-z0-9-]+')
    ->name('links.redirect');