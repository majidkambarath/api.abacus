<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Followup;
use App\Models\Job;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index(Request $request)
    {

        $user = $request->user();
        $user->load('managedBranch');

        $leads = Lead::with('business.location', 'business.contacts', 'services');

        if ($user->hasRole('executive')) {
            $leads = $leads->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $leads = $leads->where('branch_id', $user->managedBranch?->id)->with(['user', 'job.user']);
        }

        $leads = $leads->orderBy('id', 'desc')->limit(3)->get();

        $jobs = Job::with(['lead.business.location', 'lead.services', 'lead.stage:id,title']);

        if ($user->hasRole('executive')) {
            $jobs = $jobs->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $jobs = $jobs->where('branch_id', $user->managedBranch?->id)->with(['user']);
        }

        $jobs = $jobs->orderBy('id', 'desc')->limit(3)->get();

        $followups = Followup::with(['lead.business.location', 'lead.services', 'job', 'reason:id,title', 'lead.stage:id,title']);

        if ($user->hasRole('executive')) {
            $followups = $followups->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $followups = $followups->where('branch_id', $user->managedBranch?->id)->with(['user']);
        }

        $followups = $followups->orderBy('id', 'desc')->limit(3)->get();


        $year = date('Y'); // Change this to the desired year
        $leadCounts = Lead::whereYear('created_at', $year);

        if ($user->hasRole('executive')) {
            $leadCounts = $leadCounts->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $leadCounts = $leadCounts->where('branch_id', $user->managedBranch?->id);
        }

            $leadCounts = $leadCounts->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupByRaw('MONTH(created_at)')
            ->orderByRaw('MONTH(created_at)')
            ->pluck('count', 'month')
            ->toArray();

        $allMonths = range(1, 12); // Create an array with months from 1 to 12
        $leadCountsArray = [];

        foreach ($allMonths as $month) {
            $leadCountsArray[] = $leadCounts[$month] ?? 0;
        }


        $data = [
            "leads" => $leads,
            "jobs" => $jobs,
            "followups" => $followups,
            'chart' => $leadCountsArray
        ];

        return response()->json(['dashboard' => $data]);


    }


    public function changePassword(Request $request)
    {
        // Validate the request data
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8', // Add any password validation rules here
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = $request->user();

        // Check if the current password is correct
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        // Update the user's password
        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }

}
