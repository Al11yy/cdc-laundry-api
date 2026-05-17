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
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'is_active' => 'boolean'
        ]);

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
        $service->delete();
        return response()->json(['success' => true, 'message' => 'Layanan berhasil dihapus']);
    }
}