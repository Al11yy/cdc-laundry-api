<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // KUNCI UTAMA AMAN ERROR 500

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with(['customer.user', 'service', 'admin'])->latest()->get();
        return response()->json(['success' => true, 'data' => $transactions]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'service_id' => 'required|exists:services,id',
            'weight' => 'required|numeric|min:0.1',
            'payment_method' => 'required|in:cash,transfer',
            'clothes_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5000' 
        ]);

        // Proteksi Sesi Token Gantung
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Sesi login admin habis. Silakan logout lalu login kembali!'], 401);
        }

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

    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'service_id' => 'sometimes|required|exists:services,id',
            'weight' => 'sometimes|required|numeric|min:0.1',
            'payment_method' => 'sometimes|required|in:cash,transfer',
            'clothes_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5000' 
        ]);

        $data = $request->only(['customer_id', 'service_id', 'weight', 'payment_method']);

        $serviceId = $request->input('service_id', $transaction->service_id);
        $weight = $request->input('weight', $transaction->weight);
        
        $service = Service::findOrFail($serviceId);
        $data['total_price'] = $service->price * $weight;

        if ($request->hasFile('clothes_photo')) {
            if ($transaction->clothes_photo && Storage::disk('public')->exists($transaction->clothes_photo)) {
                Storage::disk('public')->delete($transaction->clothes_photo);
            }
            $data['clothes_photo'] = $request->file('clothes_photo')->store('transactions_clothes', 'public');
        }

        $transaction->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data transaksi berhasil diperbarui',
            'data' => $transaction->load(['customer.user', 'service'])
        ]);
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate(['status' => 'required|in:antrian,dicuci,disetrika,siap diambil,diambil']);
        $transaction->update(['status' => $request->status]);
        return response()->json(['success' => true, 'message' => 'Status berhasil diubah']);
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->clothes_photo && Storage::disk('public')->exists($transaction->clothes_photo)) {
            Storage::disk('public')->delete($transaction->clothes_photo);
        }
        $transaction->delete();
        return response()->json(['success' => true, 'message' => 'Transaksi berhasil dihapus']);
    }

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