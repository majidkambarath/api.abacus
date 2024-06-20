<?php

namespace App\Http\Controllers;

use App\Models\LeadStage;
use Illuminate\Http\Request;

class LeadStageController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $lead_stages = LeadStage::withCount('leads')->orderBy('id', 'desc')->get();
        return response()->json($lead_stages);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $lead_stage = LeadStage::findOrFail($id);
        return response()->json($lead_stage);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $lead_stage = LeadStage::create($request->all());
        return response()->json($lead_stage, 201);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $lead_stage = LeadStage::findOrFail($id);
        $lead_stage->update($request->all());
        return response()->json($lead_stage);
    }

    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        $lead_stage = LeadStage::findOrFail($id);
        $lead_stage->delete();
        return response()->json(null, 204);
    }
}
