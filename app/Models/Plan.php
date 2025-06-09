<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    // Specify the table associated with the model
    protected $table = 'plans';

    // Specify the fields that can be mass-assigned
    protected $fillable = [
        'title',
        'description',
        'json',
        'user_id',
    ];

    // Tell Laravel that 'json' is a JSON field and should be cast to an array or object.
    protected $casts = [
        'json' => 'array',
    ];

    // Define the relationship with the User model (One Plan belongs to one User)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
