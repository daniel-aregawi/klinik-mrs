<?php

namespace App\Models;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'user_id',
        'name',
        'email',
        'appointment_price',
        'small_description',
        'qualification',
        'consultation_price',
    ];



    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function doctorTimes()
    {
        return $this->hasMany(DoctorTime::class);
    }


    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
