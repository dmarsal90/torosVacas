<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'user', 'age', 'secret_number', 'game_over', 'attempt_number', 'previous_responses'
    ];
}
