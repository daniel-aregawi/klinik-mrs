<?php

namespace App\Models;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'total_before_discount',
        'discount_value',
        'total_after_discount',
        'tax_rate', 
        'total_with_tax'
    ];


    public function service_offer(){
        return $this->belongsToMany(Service::class,'service_offer');
    }


    static public function getUnrelatedServices($offerId)
    {
        // Retrieve services not related to the group using a join
        return Service::leftJoin('service_offer', function ($join) use ($offerId) {
                $join->on('services.id', '=', 'service_offer.service_id')
                     ->where('service_offer.offer_id', '=', $offerId);
            })
            ->whereNull('service_offer.service_id')
            ->get(['services.*']);
    }

    // public function orders()
    // {
    //     return $this->hasMany(Order::class);
    // }
}
