<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodDonor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'age',
        'sex',
        'quantity',
        'blood_type',
        'blood_name',
        'last_donation_date',
        'identity_card_id'
    ];
}
