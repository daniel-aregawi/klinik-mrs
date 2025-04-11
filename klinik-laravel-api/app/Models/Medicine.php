<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'medicine_category',
        'description',
        'price',
        'manufacturing_company',
        'status',
        'expiration_date',
        'quantity'
    ];
}
