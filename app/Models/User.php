<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Pastikan ini ada untuk login API

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi: Jika user adalah customer, dia punya 1 data detail customer
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    // Relasi: Jika user adalah admin, dia bisa melayani banyak transaksi
    public function handledTransactions()
    {
        return $this->hasMany(Transaction::class, 'admin_id');
    }
}