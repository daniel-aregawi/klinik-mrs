<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Doctor;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_doctor',
        'avatar',
        'website',
        'twitter',
        'instagram',
        'facebook',
        'bio',
        'is_pharmacist',
        'is_laboratorist',
        'is_nurse',
        'facebook_id',
        'email_verified_at',
        'active_status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function nurse()
    {
        return $this->hasOne(Nurse::class);
    }

    public function laboratorist()
    {
        return $this->hasOne(Laboratorist::class);
    }

    public function pharmacist()
    {
        return $this->hasOne(Pharmacist::class);
    }


    public function patient()
    {
        return $this->hasOne(Patient::class);
    }


    public function operationReports()
    {
        return $this->hasMany(OperationReport::class, 'user_id');
    }


    public function birthReports()
    {
        return $this->hasMany(BirthReport::class, 'user_id');
    }


    public function deathReports()
    {
        return $this->hasMany(DeathReport::class, 'user_id');
    }


    public function bedAllotments()
    {
        return $this->hasMany(BedAllotment::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // public function activityLogs()
    // {
    //     return $this->hasMany(Activity::class,'causer_id');
    // }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
