<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameRequest;
use App\Models\Game;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;


/**
 * @OA\Info(
 *     title="torosVacas API",
 *     version="1.0",
 * )
 *
 * @OA\Server(url="localhost/api/game")
 */
class GameController extends Controller
{
    /**
     * @OA\Post(
     *     path="/game",
     *     tags={"Game"},
     *     summary="Create a new game",
     *     @OA\RequestBody(
     *         description="Game creation data",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="usuario",
     *                 type="string",
     *                 description="Game identifier"
     *             ),
     *             @OA\Property(
     *                 property="edad",
     *                 type="integer",
     *                 description="User age"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Game created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="game_id",
     *                 type="integer",
     *                 description="The ID of the created game"
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/game/{game_id}/propose",
     *     tags={"Game"},
     *     summary="Propose a combination for a game",
     *     @OA\Parameter(
     *         name="game_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Combination proposal data",
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="combination",
     *                 type="string",
     *                 description="The proposed combination"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Combination proposed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="combinacion",
     *                 type="string",
     *                 description="The proposed combination"
     *             ),
     *             @OA\Property(
     *                 property="toros",
     *                 type="integer",
     *                 description="The number of 'toros' in the combination"
     *             ),
     *             @OA\Property(
     *                 property="vacas",
     *                 type="integer",
     *                 description="The number of 'vacas' in the combination"
     *             ),
     *             @OA\Property(
     *                 property="intentos",
     *                 type="integer",
     *                 description="The number of attempts made"
     *             ),
     *             @OA\Property(
     *                 property="tiempo_restante",
     *                 type="integer",
     *                 description="The remaining time in the game"
     *             ),
     *             @OA\Property(
     *                 property="evaluacion",
     *                 type="integer",
     *                 description="The evaluation score of the game"
     *             ),
     *             @OA\Property(
     *                 property="ranking",
     *                 type="integer",
     *                 description="The ranking of the game"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Error message"
     *             )
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\SecurityScheme(
     *     securityScheme="bearerAuth",
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT"
     * )
     */

    /**
     * @OA\Delete(
     *     path="/game/{game_id}/deleteGame",
     *     tags={"Game"},
     *     summary="Delete a game",
     *     @OA\Parameter(
     *         name="game_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Game deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Success message indicating the game was deleted successfully"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Game not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Error message indicating the game was not found"
     *             )
     *         )
     *     )
     * )
     */


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

    /**
     * @OA\Get(
     *     path="/game/{game_id}/previous/{attempt_number}",
     *     tags={"Game"},
     *     summary="Get the previous response for a game",
     *     @OA\Parameter(
     *         name="game_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="attempt_number",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Previous response retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="combination",
     *                 type="string",
     *                 description="The proposed combination for the specified attempt"
     *             ),
     *             @OA\Property(
     *                 property="toros",
     *                 type="integer",
     *                 description="The number of 'toros' in the proposed combination"
     *             ),
     *             @OA\Property(
     *                 property="vacas",
     *                 type="integer",
     *                 description="The number of 'vacas' in the proposed combination"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid attempt number",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Error message indicating the attempt number is not valid"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No response found for the specified attempt",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Error message indicating no response was found for the specified attempt"
     *             )
     *         )
     *     )
     * )
     */
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

        // Calcular el número de toros y vacas en el intento especificado
        $evaluation = $this->evaluateCombination($game->secret_number, $combination);
        $toros = $evaluation['toros'];
        $vacas = $evaluation['vacas'];

        // Devolver la combinación, toros y vacas en el intento especificado
        return response()->json([
            'combination' => $combination,
            'toros' => $toros,
            'vacas' => $vacas,
        ]);
    }


}
