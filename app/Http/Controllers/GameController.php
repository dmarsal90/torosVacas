<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function createGame(Request $request)
    {
        $request->validate([
            'user' => 'required|string',
            'age' => 'required|integer',
        ]);

        $game = Game::create([
            'user' => $request->user,
            'age' => $request->age,
            'secret_number' => $this->generateSecretNumber(),
        ]);

        return response()->json(['game_id' => $game->id], 201);
    }

    private function generateSecretNumber()
    {
    }
}
