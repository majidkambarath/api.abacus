<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::withCount(['leads'])->orderBy('id', 'desc')->get();
        return response()->json(['services' => $services]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $service = Service::create($data);
        $service->loadCount('leads');

        return response()->json(['service' => $service], 201);
    }

    public function show($id)
    {
        $service = Service::findOrFail($id);
        return response()->json(['service' => $service]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $data = $request->validate([
            'name' => 'string|max:255',
        ]);

        $service->update($data);
        $service->loadCount('leads');

        return response()->json(['service' => $service]);
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}
