<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $lead_sources = LeadSource::withCount('leads')->orderBy('id', 'desc')->get();
        return response()->json($lead_sources);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $lead_source = LeadSource::findOrFail($id);
        return response()->json($lead_source);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $lead_source = LeadSource::create($request->all());
        return response()->json($lead_source, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $lead_source = LeadSource::findOrFail($id);
        $lead_source->update($request->all());
        return response()->json($lead_source);
    }

    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        $lead_source = LeadSource::findOrFail($id);
        $lead_source->delete();
        return response()->json(null, 204);
    }
}
