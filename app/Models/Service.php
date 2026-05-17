<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'service_name', 
        'price', 
        'unit', 
        'is_active'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}