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

    public function proposeCombination(Request $request, $game_id): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'combination' => 'nullable|digits:4',
        ]);

        $game = Game::findOrFail($game_id);

        if ($game->game_over) {
            return response()->json(['message' => 'Game Over'], 400);
        }

        $evaluation = $this->evaluateCombination($game->secret_number, $request->combination);

        if ($evaluation['toros'] === 4) {
            $game->game_over = true;
            $game->save();
        }

        return response()->json([
            'combination' => $request->combination,
            'toros' => $evaluation['toros'],
            'vacas' => $evaluation['vacas'],
        ]);
    }

    private function evaluateCombination($secret, $proposal)
    {
        $toros = 0;
        $vacas = 0;

        $secretArray = str_split($secret);
        $proposalArray = str_split($proposal);

        for ($i = 0; $i < 4; $i++) {
            if ($secretArray[$i] === $proposalArray[$i]) {
                $toros++;
            } elseif (in_array($proposalArray[$i], $secretArray)) {
                $vacas++;
            }
        }

        return ['toros' => $toros, 'vacas' => $vacas];
    }

}
