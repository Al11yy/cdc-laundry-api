<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::all();
        return response()->json(['success' => true, 'data' => $services]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'is_active' => 'boolean',
            'service_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:25000' // Maks 25MB biar bisa upload foto layanan yang lebih besar
        ]);

        if ($request->hasFile('service_photo')) {
            $validated['service_photo'] = $request->file('service_photo')->store('services', 'public');
        }

        $service = Service::create($validated);
        return response()->json(['success' => true, 'message' => 'Layanan berhasil ditambahkan', 'data' => $service], 201);
    }

    public function show(Service $service)
    {
        return response()->json(['success' => true, 'data' => $service]);
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'service_name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string|max:20',
            'is_active' => 'boolean'
        ]);

        $service->update($validated);

        return response()->json(['success' => true, 'message' => 'Layanan berhasil diupdate', 'data' => $service]);
    }

    public function destroy(Service $service)
    {
        // Cek apakah layanan ini udah pernah dipake di transaksi
        if ($service->transactions()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Layanan ini tidak bisa dihapus karena sudah memiliki transaksi!'], 400);
        }
        
        $service->delete();
        return response()->json(['success' => true, 'message' => 'Layanan berhasil dihapus']);
    }
}