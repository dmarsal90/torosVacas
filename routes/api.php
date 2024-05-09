<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');

Route::prefix('game')->group(function () {
    Route::post('/create', [GameController::class, 'createGame']);
    Route::post('/{game_id}/propose', [GameController::class, 'proposeCombination']);
    Route::delete('/{game_id}/deleteGame', [GameController::class, 'deleteGame'])->middleware('auth:sanctum');
    Route::get('/{game_id}/previous-response/{attempt_number}', [GameController::class, 'getPreviousResponse']);
});
Route::get('/api/documentation', [SwaggerController::class, 'api'])->name('l5-swagger.api');
