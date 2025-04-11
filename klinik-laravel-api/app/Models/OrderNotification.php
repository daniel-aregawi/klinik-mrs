<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'read_at',
        'order_id'
    ];

    public function order(){
        $this->belongsTo(Order::class, 'order_id');
    }
}
