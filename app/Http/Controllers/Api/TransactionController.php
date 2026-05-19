<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    // 1. Tampil Semua Transaksi (Buat Web Admin)
    public function index()
    {
        $transactions = Transaction::with(['customer.user', 'service', 'admin'])->latest()->get();
        return response()->json(['success' => true, 'data' => $transactions]);
    }

    // 2. Simpan Transaksi Baru
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'service_id' => 'required|exists:services,id',
            'weight' => 'required|numeric|min:0.1',
            'payment_method' => 'required|in:cash,transfer',
            // Pastikan gambar di bawah 2MB biar gak ditolak Laravel
            'clothes_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:25000' 
        ]);

        $service = Service::findOrFail($request->service_id);
        $totalPrice = $service->price * $request->weight;
        $invoiceCode = 'LND-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $paymentStatus = $request->payment_method === 'cash' ? 'paid' : 'pending';
        $paidAt = $request->payment_method === 'cash' ? now() : null;

        $clothesPhotoPath = null;
        if ($request->hasFile('clothes_photo')) {
            $clothesPhotoPath = $request->file('clothes_photo')->store('transactions_clothes', 'public');
        }

        $transaction = Transaction::create([
            'invoice_code' => $invoiceCode,
            'admin_id' => Auth::id(), 
            'customer_id' => $request->customer_id,
            'service_id' => $request->service_id,
            'weight' => $request->weight,
            'total_price' => $totalPrice,
            'status' => 'antrian',
            'payment_method' => $request->payment_method,
            'payment_status' => $paymentStatus,
            'clothes_photo' => $clothesPhotoPath,
            'paid_at' => $paidAt,
        ]);

        return response()->json(['success' => true, 'message' => 'Transaksi berhasil dibuat', 'data' => $transaction], 201);
    }

    // 3. Update Status (Buat Nanti)
    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'required|in:antrian,dicuci,disetrika,siap diambil,diambil'
        ]);
        $transaction->update(['status' => $request->status]);

        return response()->json(['success' => true, 'message' => 'Status cucian berhasil diperbarui', 'data' => $transaction]);
    }

    // 4. Hapus Transaksi
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
    }

    // 5. Cek Status (Khusus Mobile App)
    public function customerStatus()
    {
        $user = Auth::user();
        if ($user->role !== 'customer') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $customerId = $user->customer->id;
        $transactions = Transaction::with('service')
                            ->where('customer_id', $customerId)
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json(['success' => true, 'data' => $transactions]);
    }
}