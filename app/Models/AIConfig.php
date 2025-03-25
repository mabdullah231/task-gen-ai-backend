<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIConfig extends Model
{
    use HasFactory;

    protected $table = 'ai_config';

    protected $fillable = ['api_key', 'model', 'temperature'];
}
