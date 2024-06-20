<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $query = request()->input('q'); // Get the 'q' query parameter

        $perPage = request()->input('perPage', 7); // Number of items per page
        $page = request()->input('page', 1); // Current page

        $branches = Branch::with(["manager"])
            ->withCount(["leads", "users"])
            ->orderBy('id', 'desc');

        // Check if the 'q' parameter is present and has a value
        if ($query) {
            $branches->where('name', 'like', '%' . $query . '%');
        }

        $branches = $branches->paginate($perPage, ['*'], 'page', $page);

        $paginationData = new CustomPaginationResource($branches);

        return response()->json(['branches' => $branches->getCollection(), 'pagination' => $paginationData]);
    }

    public function allBranches(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('include');
        $showAll = $request->input('show');
        $branchesQuery = Branch::select('id', 'name');

        if(!$showAll){
            $branchesQuery->doesntHave('manager');
        }

        if ($query) {
            // Check if 'include' parameter is set and numeric
            if (is_numeric($query)) {
                $branchId = (int)$query;
                $branch = Branch::find($branchId);
                if ($branch) {
                    $branchesQuery->orWhere('id', $branchId);
                }
            }
        }

        $branches = $branchesQuery->orderBy('id', 'desc')->get();

        return response()->json(['branches' => $branches, 'show' => $showAll]);
    }


    public function show($id): \Illuminate\Http\JsonResponse
    {
        // Get a single branch by ID
        $branch = Branch::find($id);
        $branch->load([
            'manager:id,name',
            'users:id,name,contact_number',
            'users.leads:leads.user_id',
            'leads.business.location',
            'leads.services',
            'users.jobs:user_id'
        ]);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        return response()->json(['branch' => $branch]);
    }

    public function store(Request $request)
    {
        // Validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
//            'state' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email_address' => 'required|email',
//            'branch_manager_id' => 'required|exists:users,id',
        ];

        $this->validate($request, $rules);

        // Create a new branch
        $branch = Branch::create($request->all());
        $branch->load('manager', 'leads', 'users');

        // Add leads_count and users_count properties to the branch array
        $branch->leads_count = count($branch->leads);
        $branch->users_count = count($branch->users);

        return response()->json(['branch' => $branch], 201);
    }

    public function update(Request $request, $id)
    {
        // Validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
//            'state' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email_address' => [
                'required',
                'email'
            ],
//            'branch_manager_id' => 'required|exists:users,id',
        ];

        $this->validate($request, $rules);

        // Find the branch by ID
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        // Update the branch
        $branch->update($request->all());
        $branch->load('manager', 'leads', 'users');

        // Add leads_count and users_count properties to the branch array
        $branch->leads_count = count($branch->leads);
        $branch->users_count = count($branch->users);


        return response()->json(['branch' => $branch], 200);
    }

    public function destroy($id)
    {
        // Find the branch by ID
        $branch = Branch::find($id);

        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        // Delete the branch
        $branch->delete();

        return response()->json(['message' => 'Branch deleted'], 204);
    }
}
