<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'gender',
        'blood_group',
        'date_of_birth',
        'date_of_death',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
