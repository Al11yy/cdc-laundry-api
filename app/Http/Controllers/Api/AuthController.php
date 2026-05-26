<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // API Login [cite: 63]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // API Logout [cite: 63]
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
    
    // API Profile untuk mengambil data user yang login
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('customer')
        ]);
    }

    // API Update Profile untuk memperbarui nama, phone, dan address
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'address' => 'required|string',
        ]);

        // Update nama user
        $user->update([
            'name' => $request->name,
        ]);

        // Update detail customer
        if ($user->customer) {
            $user->customer->update([
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        } else {
            $user->customer()->create([
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user->load('customer')
        ]);
    }
}