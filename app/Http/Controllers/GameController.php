<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameRequest;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function createGame(GameRequest $request)
    {
        $game = Game::create([
            'user' => $request->user,
            'age' => $request->age,
            'secret_number' => $this->generateSecretNumber(),
        ]);

        return response()->json(['game_id' => $game->id], 201);
    }

    private function generateSecretNumber()
    {
        $digits = 4;
        $min = 10 ** ($digits - 1);
        $max = (10 ** $digits) - 1;
        return strval(rand($min, $max));
    }
}
