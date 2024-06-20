<?php
namespace App\Http\Controllers;

use App\Exports\FollowupExport;
use App\Http\Resources\CustomPaginationResource;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Models\Followup;
use App\Models\Lead;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
class FollowupController extends Controller
{
    public function index(Request $request)
    {
        $query = request()->input('q'); // Get the 'q' query parameter
        $perPage = request()->input('perPage', 5); // Number of items per page
        $page = request()->input('page', 1); // Current page

        $job_id = request()->input('job_id');
        $date = request()->input('date');
        $reason_id = request()->input('reason_id');
        $status = request()->input('status');
        $contact_type = request()->input('contact_type');
        $branch = request()->input('branch');
        $stage = request()->input('stage');

        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        $user = $request->user();
        $user->load('managedBranch');
        // Load leads and their associated followups

        $followups = Job::has('followups') // Ensure jobs have at least one followup
        ->with([
            'followups' => function ($query) {
                $query->latest()->with(['reason']);
            },
            'lead' => function ($query) {
                $query->select('id', 'business_id', 'branch_id', 'status', 'lead_status', 'urgency', 'lead_stage_id')
                    ->with([
                        'business:id,name,location_id',
                        'business.location:id,name',
                    ]);
            },
            'lead.services',
            'lead.stage:id,title',
            'lead.source:id,title',
            'branch:id,name',
        ]);

        if (request()->has('job_id') && $job_id != 'all') {
            $followups->where('id', $job_id);
        }
//        if (request()->has('reason_id') && $reason_id != 'all') {
//            $followups->where('followup_reason_id', $reason_id);
//        }
//
//        if (request()->has('date') && $date != 'all') {
//            $formatted_date = date('Y-m-d', strtotime($date));
//            $followups->where('date', $formatted_date);
//        }
//        if (request()->has('status') && $status != 'all') {
//            $followups->where('status', $status);
//        }
//        if (request()->has('contact_type') && $contact_type != 'all') {
//            $followups->where('contact_type', $contact_type);
//        }
        if (request()->has('branch') && $branch != 'all') {
            $followups->where('branch_id', $branch);
        }
        if ($stage != 'all') {
            $followups->whereHas('lead', function ($queryBuilder) use ($query, $stage) {
                $queryBuilder->where('lead_stage_id', $stage);
            });
        }

        if ($start_date != 'all' & $end_date == 'all') {
            $formatted_start_date = date('Y-m-d', strtotime($start_date));

            $followups->whereHas('followups', function ($queryBuilder) use ($formatted_start_date) {
                $queryBuilder->whereDate('created_at', $formatted_start_date);
            });
        }
        if ($end_date != 'all' & $start_date == 'all') {
            $formatted_end_date = date('Y-m-d', strtotime($end_date));

            $followups->whereHas('followups', function ($queryBuilder) use ($formatted_end_date) {
                $queryBuilder->whereDate('created_at', $formatted_end_date);
            });
        }
        if ($start_date != 'all' && $end_date != 'all') {
            $formatted_start_date = date('Y-m-d', strtotime($start_date));
            $formatted_end_date = date('Y-m-d', strtotime($end_date));

            $followups->whereHas('followups', function ($queryBuilder) use ($formatted_start_date, $formatted_end_date) {
                $queryBuilder->whereBetween('created_at', [$formatted_start_date, $formatted_end_date]);
            });
        }


        if ($user->hasRole('executive')) {
            $followups = $followups->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $followups = $followups->where('branch_id', $user->managedBranch?->id)->with(['user']);
        }

        if (!empty($query)) {
            // If a search query is provided, filter leads by business name
            $followups->whereHas('lead.business', function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', '%' . $query . '%');
            });
        }

        $followups = $followups->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $followups->getCollection()->transform(function ($followup) {
            $followup->lead->services->transform(function ($service) {
                return [
                    'id' => $service['id'],
                    'name' => $service['name'],
                ];
            });
            return $followup;
        });

        $paginationData = new CustomPaginationResource($followups);

        return response()->json(['followups' => $followups->getCollection(), 'pagination' => $paginationData]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        $data = [];
        // Validate input
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:jobs,id',
            'date' => 'required|date',
            'reason' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data['followup_reason_id'] = $request->input('reason');
        $data['time_full'] = $request->input('time');
        $data['contact_type'] = $request->input('contact_type');
        $data['time'] = date('h:i A', strtotime($request->input('time')));
        $data['date'] = date('Y-m-d', strtotime($request->input('date')));
        $data['job_id'] = $request->input('job_id');

        $job = Job::where('id', $data['job_id'])->first();
        $data['lead_id'] = $job->lead_id;
        $data['branch_id'] = $job->branch_id;
        $data['user_id'] = $user->id;

        // Create a new followup
        $followup = Followup::create($data);
        $followup->load(['lead.business.location', 'lead.services', 'job', 'reason:id,title']);
        return response()->json(['followup' => $followup]);
    }

    public function show($id)
    {
        // Load the associated lead for the show action
        $followup = Job::with([
            'lead.services',
            'lead.stage:id,title',
            'lead.source:id,title',
            'lead.business.location.route',
            'lead.business.contacts',
            'lead.business.photos:business_id,id,photo,type,name',
            'user',
            'closedBy',
            'lead.user',
            'lead.user.branches:id,name',
            'followups',
            'followups.reason'
        ])->findOrFail($id);
        return response()->json(['followup' => $followup]);
    }

    public function update(Request $request, Followup $followup)
    {
        $data = [];
        // Validate input
        $validator = Validator::make($request->all(), [
            'job_id' => [
                'required',
            ],
            'date' => 'required|date',
            'reason' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data['followup_reason_id'] = $request->input('reason');
        $data['time_full'] = $request->input('time');
        $data['contact_type'] = $request->input('contact_type');
        $data['time'] = date('h:i A', strtotime($request->input('time')));
        $data['date'] = date('Y-m-d', strtotime($request->input('date')));
        $data['job_id'] = $request->input('job_id');

        $job = Job::where('id', $data['job_id'])->first();
        $data['lead_id'] = $job->lead_id;
        $data['branch_id'] = $job->branch_id;

        // Update the followup
        $followup->update($data);
        $followup->load([
            'lead.business.location',
            'lead.services',
            'job',
            'reason:id,title',
            'lead.stage:id,title',
            'lead.source:id,title',
        ]);
        return response()->json(['message' => 'Followup updated successfully', 'followup' => $followup]);
    }

    public function destroy(Followup $followup)
    {
        // Delete the followup
        $followup->delete();
        return response()->json(['message' => 'Followup deleted successfully']);
    }
    public function updateStatus($id, Request $request){
        $status = $request->input('status');
        $followup = Followup::find($id);
        $followup->status = $status;
        $followup->save();
        return response()->json(['message' => 'Followup updated successfully']);
    }

    public function checkDateTime(Request $request): \Illuminate\Http\JsonResponse
    {
        $date = $request->input('date');
        $time = $request->input('time');

        $user = $request->user();

        $time_formatted = date('h:i A', strtotime($time));

        $existingFollowups = Followup::where('date', $date)
            ->where('time', $time_formatted)
            ->where('user_id', $user->id)
            ->get();

        if ($existingFollowups->count() > 0) {
            return response()->json(['message' => 'exist']);
        } else {
            return response()->json(['message' => 'available']);
        }
    }

    public function followupFilters(Request $request)
    {
        $user = $request->user();
        $filters = [];

        if ($user->hasRole('admin')) {
            $followups = Followup::get();
        } elseif ($user->hasRole('manager')) {
            $user->load('managedBranch');
            $followups = Followup::where('branch_id', $user->managedBranch?->id)->get();
        } else {
            $followups = Followup::where('user_id', $user->id)->get();
        }

        $followups->load([
            'lead.services',
            'user',
            'lead.branch',
            'lead.business',
            'job',
            'reason',
            'lead.stage:id,title',
            'lead.source:id,title',
        ]);


        $services = [];
        $reasons = [];
        $jobs = [];
        $stages = [];


        foreach ($followups as $followup) {
            foreach ($followup->lead->services as $service) {
                $services[] = [
                    "id" => $service->id,
                    "name" => $service->name
                ];
            }
//            $reasons[] = $followup->reason;
            $jobs[] = [
                'id' => $followup->job->id,
                'name'=> $followup->lead->business->name
            ];
            $stages[] = [
                "id" => $followup->lead->stage->id,
                "name" => $followup->lead->stage->title
            ];
        }

        $uniqueServices = collect($services)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        $uniqueReasons = collect($reasons)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["title"]
            ];
        })->values()->toArray();

        $uniqueJobs = collect($jobs)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        $uniqueStages = collect($stages)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        if ($user->hasRole('admin')) {
            // Group leads by branch ID and get unique branches
            $uniqueBranches = $followups->groupBy('branch.id')->map(function ($group) {
                return [
                    'id' => $group[0]->branch->id,
                    'name' => $group[0]->branch->name
                ];
            })->values()->toArray();
            $filters['branches'] = $uniqueBranches;
        }

        $filters['services'] = $uniqueServices;
//        $filters['reasons'] = $uniqueReasons;
        $filters['jobs'] = $uniqueJobs;
        $filters['stages'] = $uniqueStages;

        return response()->json(['filters' => $filters], 200);


    }
    public function exportFollowups(Request $request)
    {
        $parameters = $request->all();
        $user = $request->user();
        // You can access the user role using $user->role
        $userRole = $user->roles[0]["name"];
        $userId = $user->roles[0]["name"];
        $userBranchId = '';

        if ($user->hasRole('manager')) {
            $user->load('managedBranch');
            $userBranchId = $user->managedBranch?->id;
        }
        return Excel::download(new FollowupExport($parameters, $userRole, $userId, $userBranchId), 'followups.xlsx');
    }
}

