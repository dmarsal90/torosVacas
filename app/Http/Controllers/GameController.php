<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameRequest;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function createGame(GameRequest $request): \Illuminate\Http\JsonResponse
    {
        $game = Game::create([
            'user' => $request->usuario,
            'age' => $request->edad,
            'secret_number' => $this->generateSecretNumber(),
        ]);

        return response()->json(['game_id' => $game->id], 201);
    }

    private function generateSecretNumber(): string
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

        // Guardar la combinación en el campo 'previous_responses'
        $previous_responses = json_decode($game->previous_responses, true);

// Verificar si $previous_responses es un array válido
        if (!is_array($previous_responses)) {
            $previous_responses = [];
        }

// Agregar la nueva combinación al array
        $previous_responses[] = $request->combination;

// Convertir el array actualizado a una cadena JSON
        $jsonPreviousResponses = json_encode($previous_responses);

// Verificar si json_encode() tuvo éxito
        if ($jsonPreviousResponses === false) {
            // Manejar el error, por ejemplo, lanzar una excepción o registrar un mensaje de error
            // También puedes usar json_last_error() para obtener más información sobre el error
            throw new \Exception('Error al codificar el array a JSON: ' . json_last_error_msg());
        }

// Establecer el nuevo JSON en el campo 'previous_responses'
        $game->previous_responses = $jsonPreviousResponses;

        $game->update();

        // Verificar si la combinación ya existe
        $existingGame = Game::where('secret_number', $request->combination)->first();

        if ($existingGame) {
            // Combinación duplicada
            return response()->json(['message' => 'Combinación duplicada: los dígitos ya fueron enviados previamente en el mismo orden.'], 400);
        }

        $currentTime = now();
        $gameStartTime = $game->created_at;
        $timeElapsed = $currentTime->diffInMinutes($gameStartTime);

        if ($timeElapsed >= config('game.game_over_time')) {
            $game->game_over = true;
            $game->update();
            return response()->json([
                'message' => 'Game Over: El tiempo máximo del juego fue alcanzado.',
                'secret_number' => $game->secret_number,
            ], 400);
        }

        $evaluation = $this->evaluateCombination($game->secret_number, $request->combination);

        if ($evaluation['toros'] === 4) {
            $game->game_over = true;
            $game->update();
            return response()->json([
                'combination' => $game->secret_number,
                'toros' => $evaluation['toros'],
                'vacas' => $evaluation['vacas'],
                'message' => '¡Ganaste!',
            ], 200);
        }

        // Incrementar el número de intentos
        $game->attempt_number++;
        $game->update();

        // Calcular el tiempo restante en el juego
        $timeRemaining = config('game.game_over_time') - $timeElapsed;

        // Calcular la evaluación
        $evaluationScore = ($timeElapsed / 2) + $game->attempt_number;

        // Obtener todos los juegos y calcular su evaluación
        $allGames = Game::all();
        $allGames->each(function ($game) {
            $game->evaluationScore = ($game->created_at->diffInMinutes(now()) / 2) + $game->attempt_number;
        });

// Ordenar todos los juegos por si han sido ganados o no, y luego por evaluación
        $allGames->sortByDesc(function ($game) {
            return $game->game_over? 0 : 1;
        });

        $allGames->sortByDesc(function ($game) {
            return $game->evaluationScore;
        });

// Devolver el ranking del juego actual
        $gameRank = $allGames->search($game);
        $gameRanking = $gameRank + 1; // +1 porque los rangos comienzan en 1

        return response()->json([
            'combinacion' => $request->combination,
            'toros' => $evaluation['toros'],
            'vacas' => $evaluation['vacas'],
            'intentos' => $game->attempt_number,
            'tiempo_restante' => $timeRemaining,
            'evaluacion' => $evaluationScore,
            'ranking' => $gameRanking,
        ]);
    }


    private function evaluateCombination($secret, $proposal): array
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

    public function deleteGame(Request $request, $game_id): \Illuminate\Http\JsonResponse
    {
        // Validar el identificador del juego
        $game = Game::findOrFail($game_id);

        // Eliminar el juego
        $game->delete();

        // Devolver una respuesta indicando que el juego fue eliminado con éxito
        return response()->json([
            'message' => 'Datos del juego eliminados con éxito.',
        ], 200);
    }

    public function getPreviousResponse($game_id, $attempt_number)
    {
        $game = Game::findOrFail($game_id);

        // Verificar si el número del intento es válido
        if ($attempt_number < 1 || $attempt_number > $game->attempt_number) {
            return response()->json(['message' => 'El número del intento no es válido.'], 400);
        }

        // Obtener la combinación propuesta en el intento especificado
        $previous_responses = json_decode($game->previous_responses, true);

        // Verificar si el intento especificado tiene una combinación asociada
        if (!isset($previous_responses[$attempt_number - 1])) {
            return response()->json(['message' => 'No hay combinación registrada para este intento.'], 404);
        }

        $combination = $previous_responses[$attempt_number - 1];

        // Devolver la combinación y otras informaciones relevantes
        return response()->json([
            'combination' => $combination,
            // Otras informaciones que desees devolver, como el número de toros y vacas en ese intento
        ]);
    }

}
