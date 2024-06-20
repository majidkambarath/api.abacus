<?php

namespace App\Http\Controllers;

use App\Models\FollowupReason;
use Illuminate\Http\Request;

class FollowupReasonController extends Controller
{
    public function index()
    {
        $reasons = FollowupReason::withCount('followups')->orderBy('id', 'desc')->get();
        return response()->json($reasons);
    }

    public function show($id)
    {
        $reason = FollowupReason::findOrFail($id);
        return response()->json($reason);
    }

    public function store(Request $request)
    {
        $reason = FollowupReason::create($request->all());
        $reason->loadCount('followups');
        return response()->json($reason, 201);
    }

    public function update(Request $request, $id)
    {
        $reason = FollowupReason::findOrFail($id);
        $reason->update($request->all());
        $reason->loadCount('followups');
        return response()->json($reason);
    }

    public function destroy($id)
    {
        $reason = FollowupReason::findOrFail($id);
        $reason->delete();
        return response()->json(null, 204);
    }
}
