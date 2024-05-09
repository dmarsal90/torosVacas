<?php

use App\Http\Controllers\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('game')->group(function () {
    Route::post('/create', [GameController::class, 'createGame']);
    Route::post('/{game_id}/propose', [GameController::class, 'proposeCombination']);
    Route::delete('/{game_id}/deleteGame', [GameController::class, 'deleteGame']);
    Route::get('/{game_id}/previous-response/{attempt_number}', [GameController::class, 'getPreviousResponse']);
});
