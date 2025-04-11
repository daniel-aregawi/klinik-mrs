<?php

namespace App\Models;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day',
        'start_time',
        'end_time',
        'appointment_time',
        'date'
    ];

    protected $casts = [
        'date' => 'json',
    ];

    

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
