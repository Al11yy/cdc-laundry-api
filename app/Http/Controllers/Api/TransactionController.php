<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    // 1. API Create Transaction (Untuk Web Admin)
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'service_id' => 'required|exists:services,id',
            'weight' => 'required|numeric|min:0.1',
            'payment_method' => 'required|in:cash,transfer',
            'payment_proof' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Ambil harga layanan untuk dihitung dengan berat
        $service = Service::findOrFail($request->service_id);
        $totalPrice = $service->price * $request->weight;

        // Auto-Generate Invoice Code
        $invoiceCode = 'LND-' . date('Ymd') . '-' . rand(1000, 9999);

        // Handle Logika Pembayaran
        $paymentStatus = 'pending';
        $paidAt = null;
        $paymentProofPath = null;

        if ($request->payment_method === 'cash') {
            $paymentStatus = 'paid';
            $paidAt = now();
        } elseif ($request->payment_method === 'transfer' && $request->hasFile('payment_proof')) {
            // Upload gambar pakai Laravel Storage
            $paymentProofPath = $request->file('payment_proof')->store('receipts', 'public');
        }

        // Simpan ke database
        $transaction = Transaction::create([
            'invoice_code' => $invoiceCode,
            // Pakai Auth::id() biar VS Code nggak merah
            'admin_id' => Auth::id(), 
            'customer_id' => $request->customer_id,
            'service_id' => $request->service_id,
            'weight' => $request->weight,
            'total_price' => $totalPrice,
            'status' => 'antrian',
            'payment_method' => $request->payment_method,
            'payment_status' => $paymentStatus,
            'payment_proof' => $paymentProofPath,
            'paid_at' => $paidAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dibuat',
            'data' => $transaction
        ], 201);
    }

    // 2. API Update Status Cucian (Untuk Web Admin)
    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'required|in:antrian,dicuci,disetrika,siap diambil,diambil'
        ]);

        $transaction->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status cucian berhasil diperbarui',
            'data' => $transaction
        ]);
    }

    // 3. API Cek Status Laundry (Khusus Untuk Mobile App Customer)
    public function customerStatus()
    {
        /** @var \App\Models\User $user */
        // Pakai Auth::user() biar VS Code nggak merah
        $user = Auth::user();

        // Keamanan ekstra: Pastikan yang login beneran customer
        if ($user->role !== 'customer') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        // Cari ID Customer yang berelasi dengan User login
        $customerId = $user->customer->id;

        // Tarik data transaksi milik customer tersebut beserta detail layanannya 
        $transactions = Transaction::with('service')
                            ->where('customer_id', $customerId)
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}