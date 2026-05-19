<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_name', 
        'description', 
        'price', 
        'unit', 
        'is_active', 
        'service_photo'
    ];

    public function transactions() { return $this->hasMany(Transaction::class); }
}