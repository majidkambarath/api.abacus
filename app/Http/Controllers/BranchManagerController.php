<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Branch;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BranchManagerController extends Controller
{
    public function index()
    {
        $query = request()->input('q'); // Get the 'q' query parameter

        $perPage = request()->input('perPage', 7); // Number of items per page
        $page = request()->input('page', 1); // Current page

        $managers = User::whereHas('roles', function ($query) {
            $query->where('roles.id', 2);
        })
            ->with(['managedBranch'])
            ->when($query, function ($query, $searchQuery) {
                // Check if the 'q' parameter is present and has a value
                if ($searchQuery) {
                    $query->where('name', 'like', '%' . $searchQuery . '%');
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginationData = new CustomPaginationResource($managers);

        return response()->json(['managers' => $managers->getCollection(), 'pagination' => $paginationData]);
    }

    public function allManagers(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->input('include');

        $managersQuery = User::whereHas('roles', function ($query) {
            $query->where('roles.id', 2);
        })
            ->select('id', 'name')
            ->doesntHave('managedBranch'); // Filters managers with null managedBranch

        if ($query) {
            // Check if 'include' parameter is set and numeric
            if (is_numeric($query)) {
                $managerId = (int)$query;
                $manager = User::find($managerId);
                if ($manager) {
                    $managersQuery->orWhere('id', $managerId);
                }
            }
        }

        $managers = $managersQuery->orderBy('id', 'desc')->get();

        return response()->json(['managers' => $managers]);
    }

    public function show($id)
    {
        // Get a single branch manager by ID
        $manager = User::find($id);

        if (!$manager) {
            return response()->json(['message' => 'Manager not found'], 404);
        }
        $manager->load(['managedBranch.leads.services', 'managedBranch.leads.business.location',]);


        return response()->json(['manager' => $manager]);
    }

    public function store(Request $request)
    {
        // Validation rules for creating a new branch manager
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];

        $this->validate($request, $rules);

        // Create a new user as a branch manager
        $manager = new User();
        $manager->name = $request->input('name');
        $manager->address = $request->input('address');
        $manager->email = $request->input('email');
        $manager->contact_number = $request->input('contact_number');

        $joinDate = date('Y-m-d', strtotime($request->input('join_date')));

        $manager->join_date = $request->input('join_date') ? $joinDate : null;

        $gender = $request->input('gender');
        $manager->gender = $gender ? $gender : null;
        $manager->password = Hash::make('12345678');
        $manager->save();

        $branch_id = $request->input('branch');
        if($branch_id){
            $branch = Branch::find($branch_id);
            $branch->branch_manager_id = $manager->id;
            $branch->save();
        }

        // Attach the role (role_id 2) to the branch manager
        $manager->roles()->attach(2); // Assuming role_id 2 is for branch managers

        $manager->load('managedBranch');

        return response()->json(['manager' => $manager], 201);

    }

    public function update(Request $request, $id)
    {
        // Validation rules for updating a branch manager
        $rules = [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
        ];

        $this->validate($request, $rules);

        // Find the branch manager by ID
        $manager = User::find($id);

        if (!$manager) {
            return response()->json(['message' => 'Manager not found'], 404);
        }

        // Update branch manager information
        $manager->name = $request->input('name', $manager->name);
        $manager->email = $request->input('email', $manager->email);

        $manager->address = $request->input('address', $manager->address);
        $manager->contact_number = $request->input('contact_number', $manager->contact_number);

        $joinDate = date('Y-m-d', strtotime($request->input('join_date')));

        $manager->join_date = $request->input('join_date') ? $joinDate : null;


        $gender = $request->input('gender', $manager->gender);
        $manager->gender = $gender ? $gender : null;

//        if ($request->has('password')) {
//            $manager->password = Hash::make($request->input('password'));
//        }

        $branch_id = $request->input('branch');

        if ($branch_id) {
            $branch = Branch::find($branch_id);

            if ($branch) {
                $previousManagerBranch = Branch::where('branch_manager_id', $id)->first();
                if ($previousManagerBranch && $previousManagerBranch->id !== $branch_id) {
                        // Set the previous manager's branch's branch_manager_id to null
                        $previousManagerBranch->branch_manager_id = null;
                        $previousManagerBranch->save();
                }
                // Set the new manager's branch
                $branch->branch_manager_id = $id;
                $branch->save();
            }
        }

        $manager->save();

        $manager->load('managedBranch');

        return response()->json(['manager' => $manager]);
    }

    public function destroy($id)
    {
        // Find the branch manager by ID
        $manager = User::find($id);

        if (!$manager) {
            return response()->json(['message' => 'Manager not found'], 404);
        }

        // Detach the branch manager's role (role_id 2) if attached
        $manager->roles()->detach(2); // Assuming role_id 2 is for branch managers

        // Delete the branch manager
        $manager->delete();

        return response()->json(['message' => 'Manager deleted']);
    }
}
