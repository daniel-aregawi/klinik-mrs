<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BirthReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'details',
        'date',
        'patient_name',
        'doctor_name',
        'patient_email',
        'doctor_email',
        'doctor_id',
        'patient_id'
    ];


    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

}
