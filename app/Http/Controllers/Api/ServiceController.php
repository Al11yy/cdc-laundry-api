<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // KUNCI UTAMA AMAN ERROR 500

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::latest()->get();
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
            'service_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5000'
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
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'unit' => 'sometimes|required|string|max:20',
            'is_active' => 'boolean',
            'service_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:5000'
        ]);

        if ($request->hasFile('service_photo')) {
            if ($service->service_photo && Storage::disk('public')->exists($service->service_photo)) {
                Storage::disk('public')->delete($service->service_photo);
            }
            $validated['service_photo'] = $request->file('service_photo')->store('services', 'public');
        }

        $service->update($validated);
        return response()->json(['success' => true, 'message' => 'Layanan berhasil diupdate', 'data' => $service]);
    }

    public function destroy(Service $service)
    {
        if ($service->transactions()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Layanan ini tidak bisa dihapus karena sudah memiliki transaksi!'], 400);
        }
        
        if ($service->service_photo && Storage::disk('public')->exists($service->service_photo)) {
            Storage::disk('public')->delete($service->service_photo);
        }

        $service->delete();
        return response()->json(['success' => true, 'message' => 'Layanan berhasil dihapus']);
    }
}