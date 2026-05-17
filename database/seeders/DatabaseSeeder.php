<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Service;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Akun Admin 
        User::create([
            'name' => 'Admin CDC Laundry',
            'email' => 'admin@cdclaundry.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // 2. Buat Data Services (Layanan Laundry) 
        Service::insert([
            [
                'service_name' => 'Cuci Kiloan Reguler',
                'price' => 7000.00,
                'unit' => 'Kg',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_name' => 'Cuci Satuan Selimut',
                'price' => 25000.00,
                'unit' => 'Pcs',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'service_name' => 'Setrika Saja',
                'price' => 5000.00,
                'unit' => 'Kg',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}