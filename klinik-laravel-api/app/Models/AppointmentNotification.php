<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'read_at',
    ];
}
