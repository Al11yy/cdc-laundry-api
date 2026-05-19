<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index()
    {
        // Ambil data customer beserta relasi akun user-nya
        $customers = Customer::with('user')->get();
        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|numeric',
            'address' => 'required|string',
            'password' => 'required|min:6' // Validasi password baru
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Pake password dari input React
                'role' => 'customer'
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'address' => $request->address
            ]);

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Pelanggan berhasil ditambahkan.',
                'data' => $customer->load('user')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan pelanggan', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Customer $customer)
    {
        return response()->json(['success' => true, 'data' => $customer->load('user')]);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $customer->user_id,
            'phone' => 'sometimes|required|numeric',
            'address' => 'sometimes|required|string'
        ]);

        try {
            DB::beginTransaction();

            // Update nama dan email di tabel users jika dikirim
            if ($request->has('name') || $request->has('email')) {
                $customer->user->update($request->only(['name', 'email']));
            }

            // Update phone dan address di tabel customers
            $customer->update($request->only(['phone', 'address']));

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Data pelanggan berhasil diupdate', 'data' => $customer->load('user')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal mengupdate pelanggan', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Customer $customer)
    {
        // Karena di migration kita pakai onDelete('cascade'), 
        // kita cukup hapus user-nya, maka data customer otomatis terhapus.
        $customer->user->delete();
        
        return response()->json(['success' => true, 'message' => 'Pelanggan berhasil dihapus']);
    }
}