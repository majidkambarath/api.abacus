<?php

namespace App\Http\Controllers;

use App\Models\JobDocumentType;
use Illuminate\Http\Request;

class JobDocumentTypesController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $job_document_types = JobDocumentType::withCount('documents')->orderBy('id', 'desc')->get();
        return response()->json($job_document_types);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $job_document_type = JobDocumentType::findOrFail($id);
        return response()->json($job_document_type);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $job_document_type = JobDocumentType::create($request->all());
        return response()->json($job_document_type, 201);
    }

    public function update(Request $request, $id)
    {
        $job_document_type = JobDocumentType::findOrFail($id);
        $job_document_type->update($request->all());
        return response()->json($job_document_type);
    }

    public function destroy($id)
    {
        $job_document_type = JobDocumentType::findOrFail($id);
        $job_document_type->delete();
        return response()->json(null, 204);
    }
}
