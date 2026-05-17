<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 50)->unique();
            $table->foreignId('admin_id')->constrained('users');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('service_id')->constrained('services');
            
            // Fix Jebakan: Kolom berat yang diwajibkan di UI namun hilang di blueprint
            $table->decimal('weight', 5, 2); 
            
            $table->decimal('total_price', 12, 2);
            
            // Enum Status sesuai dengan Blueprint Database
            $table->enum('status', ['antrian', 'dicuci', 'disetrika', 'siap diambil', 'diambil'])->default('antrian');
            
            $table->enum('payment_method', ['cash', 'transfer']);
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            
            // Path/URL Foto Bukti Transfer (Nullable jika cash)
            $table->string('payment_proof')->nullable(); 
            
            $table->timestamp('paid_at')->nullable();
            
            // Fix Jebakan: Wajib ada untuk hitung API Statistik (tanggal pembuatan transaksi)
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
