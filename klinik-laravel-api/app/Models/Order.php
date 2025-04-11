<?php

namespace App\Models;

use App\Models\User;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'total_price',
        'session_id',
        'user_id',
        'order_id',
        'full_name',
        'gender',
        'national_card_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function offer()
    {
        return $this->belongsTo(Offer::class,'offer_id');
    }

    public function notifications(){
        $this->hasMany(OrderNotification::class);
    }
}
