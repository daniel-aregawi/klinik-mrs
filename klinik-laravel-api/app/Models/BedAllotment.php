<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedAllotment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bed_number',
        'bed_type',
        'patient_email',
        'patient_name',
        'allotment_time',
        'discharge_time',
        'patient_id'
    ];


    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
