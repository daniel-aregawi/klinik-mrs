<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Offer;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'price',
        'description',
        'status'
    ];

    public function service_offer(){
        return $this->belongsToMany(Offer::class,'service_offer');
    }
}
