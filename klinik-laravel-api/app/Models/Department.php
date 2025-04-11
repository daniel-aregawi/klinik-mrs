<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'desc',
    ];

    public function operationReports()
    {
        return $this->hasMany(OperationReport::class);
    }
}
