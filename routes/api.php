<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MikrotikMonitorController;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// MikroTik API routes
Route::prefix('mikrotik')->group(function () {
    Route::get('/interfaces', [MikrotikMonitorController::class, 'interfaces']);
    Route::get('/firewall', [MikrotikMonitorController::class, 'firewall']);
    Route::get('/routing', [MikrotikMonitorController::class, 'routing']);
});
