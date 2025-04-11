<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vaccine extends Model
{
    use HasFactory;


    protected $fillable = [
        'patient_id',
        'given_by',
        'vaccine_name',
        'serial_number',
        'dose_number',
        'date_given',
        'note', 
        'nurse_name',
        'patient_name',
        'patient_email',
        'nurse_email'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class,'patient_id');
    }

    public function nurse()
    {
        return $this->belongsTo(Nurse::class,'given_by');
    }
}
