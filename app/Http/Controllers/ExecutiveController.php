<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserDocument;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ExecutiveController extends Controller
{
    public function index()
    {
        // Get the currently authenticated user (manager)
        $manager = Auth::user();

        $query = request()->input('q'); // Get the 'q' query parameter
        $perPage = request()->input('perPage', 7); // Number of items per page
        $page = request()->input('page', 1); // Current page

        // Get the managed branch for the current manager
        $managedBranch = $manager->managedBranch;

        // Check if the manager has a managed branch
        if ($managedBranch) {
            // Create the base query to get executives (users with role_id = 3) belonging to the managed branch
            $executivesQuery = $managedBranch->users()
                ->whereHas('roles', function ($query) {
                    $query->where('roles.id', 3); // Role ID 3 represents executives
                })
                ->withCount(['leads as total_leads', 'jobs as total_jobs'])
                ->with('executiveDocuments')
                ->orderBy('id', 'desc');

            // Apply the search filter if a query is provided
            if ($query) {
                $executivesQuery->where('name', 'like', "%$query%");
            }

            // Add a count for closed jobs
            $executives = $executivesQuery->withCount(['jobs as jobs_closed' => function ($query) {
                $query->where('status', 0); // Count jobs with status 0
            }])->paginate($perPage, ['*'], 'page', $page);

            $paginationData = new CustomPaginationResource($executives);


            return response()->json([
                'executives' => $executives->getCollection(),
                'pagination' => $paginationData,
            ]);
        }

        return response()->json(['executives' => []]); // Return an empty array or an error message
    }

    public function allExecutives()
    {
        $manager = Auth::user();
        $managedBranch = $manager->managedBranch;
        if ($managedBranch) {
            $executives = $managedBranch->users()
                ->whereHas('roles', function ($query) {
                    $query->where('roles.id', 3);
                })->select('users.id', 'users.name')->orderBy('id', 'desc')->get();

            return response()->json(['executives' => $executives]); // Return an empty array or an error message

        }
        return response()->json(['executives' => []]); // Return an empty array or an error message

    }


    public function show($id)
    {
        // Get a single branch executive by ID
        $executive = User::find($id);

        $executive->load([
            'executiveDocuments',
            'leads.business.location',
            'leads.services',
            'jobs.lead.business.location',
            'jobs.lead.services',
            'jobs.lead.stage:id,title',
            'jobs.lead.source:id,title',
        ]);

        if (!$executive) {
            return response()->json(['message' => 'executive not found'], 404);
        }

        return response()->json(['executive' => $executive]);
    }

    public function store(Request $request)
    {
        // Validation rules for creating a new branch executive
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];

        $this->validate($request, $rules);

        // Create a new user as a branch executive
        $executive = new User();
        $executive->name = $request->input('name');
        $executive->address = $request->input('address');
        $executive->email = $request->input('email');
        $executive->contact_number = $request->input('contact_number');

        $joinDate = date('Y-m-d', strtotime($request->input('join_date')));
        $executive->join_date = $request->input('join_date')  ? $joinDate :  null;

        $gender = $request->input('gender');
        $executive->gender = $gender ? $gender : null;
        $executive->password = Hash::make('12345678');
        $executive->save();

        // Attach the role (role_id 2) to the branch executive
        $executive->roles()->attach(3);

        $user = $request->user();
        $user->load('managedBranch');
        $manager_branch = $user->managedBranch?->id;
        if($manager_branch) {
            $branch = Branch::find($manager_branch);
            $branch->users()->attach($executive);
        }

        $executive->total_leads = $executive->leads()->count();
        $executive->load('executiveDocuments');

        return response()->json(['executive' => $executive], 201);

    }

    public function uploadDocuments(Request $request)
    {
        $executiveId = $request->input('id');
        $files = $request->allFiles();

        // If there are uploaded files
        if (!empty($files)) {
            foreach ($files as $file) {
                // Get the original filename
                $originalFilename = $file->getClientOriginalName();

                // Extract the file extension
                $extension = $file->getClientOriginalExtension();

                // Generate a unique filename with a timestamp before the extension
                $timestamp = now()->format('YmdHis'); // Format the timestamp as desired
                $newFilename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $extension;

                $mime_type = $file->getClientMimeType();

                // Validate and store the file with the new filename
                $path = $file->storeAs('uploads/documents/' . $executiveId, $newFilename);

                // Create a new ExecutiveDocument record
                UserDocument::create([
                    'user_id' => $executiveId, // Associate with the correct executive
                    'file_path' => $path,
                    'mime_type' => $mime_type,
                    'file_name' => $originalFilename
                ]);
            }

            return response()->json("Files processed successfully.", 201);
        }

        return response()->json("No files uploaded.", 200);
    }



    public function update(Request $request, $id)
    {
        // Validation rules for updating a branch executive
        $rules = [
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $id,
        ];

        $this->validate($request, $rules);

        // Find the branch executive by ID
        $executive = User::find($id);

        if (!$executive) {
            return response()->json(['message' => 'executive not found'], 404);
        }

        // Update branch executive information
        $executive->name = $request->input('name', $executive->name);
        $executive->email = $request->input('email', $executive->email);

        $executive->address = $request->input('address', $executive->address);
        $executive->contact_number = $request->input('contact_number', $executive->contact_number);

        $joinDate = date('Y-m-d', strtotime($request->input('join_date')));
        $executive->join_date = $joinDate ?: null;

        $gender = $request->input('gender', $executive->gender);
        $executive->gender = $gender ? $gender : null;

        $executive->save();
        $executive->total_leads = $executive->leads()->count();

        $executive->load('executiveDocuments');

        return response()->json(['executive' => $executive]);
    }

    public function destroy($id)
    {
        // Find the branch executive by ID
        $executive = User::find($id);

        if (!$executive) {
            return response()->json(['message' => 'executive not found'], 404);
        }

        // Detach the branch executive's role (role_id 2) if attached
        $executive->roles()->detach(3); // Assuming role_id 2 is for branch executives

        // Delete the branch executive
        $executive->delete();

        return response()->json(['message' => 'executive deleted']);
    }
}
