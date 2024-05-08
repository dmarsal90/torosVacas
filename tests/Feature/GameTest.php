<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateGameReturnsGameId()
    {
        $response = $this->postJson('/api/game/create', [
            'user' => 'John Doe',
            'age' => 30,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['game_id']);
    }

    public function testProposeCombinationReturnsCorrectCombination()
    {
        $game = Game::create([
            'user' => 'John Doe',
            'age' => 30,
            'secret_number' => '1234',
        ]);

        $response = $this->postJson("/api/game/{$game->id}/propose", [
            'combination' => '1234',
        ]);

        $response->assertStatus(200)
            ->assertJson(['combination' => '1234']);
    }

    public function testProposeCombinationForCompletedGame()
    {
        $game = Game::create([
            'user' => 'John Doe',
            'age' => 30,
            'secret_number' => '1234',
            'game_over' => true,
        ]);

        $response = $this->postJson("/api/game/{$game->id}/propose", [
            'combination' => '5678',
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Game Over']);
    }


    public function testCreateGameValidationFailsWithInvalidData()
    {
        $response = $this->postJson('/api/game/create', [
            'user' => '',
            'age' => 'text', // Invalid data type
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user', 'age']);
    }

}
