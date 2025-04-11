<?php

namespace App\Models;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'doctor_id',
        'day',
        'start_time',
        'end_time',
        'description',
        'status',
        'order_id',
        'session_id',
        'user_id',
        'general_status',
        'completed',
        'date',
        'route'
    ];


    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
