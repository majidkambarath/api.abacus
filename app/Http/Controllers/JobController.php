<?php

namespace App\Http\Controllers;

use App\Exports\JobExport;
use App\Http\Resources\CustomPaginationResource;
use App\Models\Job;
use App\Models\JobDocuments;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = request()->input('q'); // Get the 'q' query parameter
        $perPage = request()->input('perPage', 5); // Number of items per page
        $page = request()->input('page', 1); // Current page

        // filters
        $branch = request()->input('branch');
        $assigned_to = request()->input('assigned_to');
        $service = request()->input('service');
        $classification = request()->input('classification');
        $urgency = request()->input('urgency');
        $status = request()->input('status');
        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        $stage = request()->input('stage');

        $user = $request->user();
        $user->load('managedBranch');
        $queryParams = $request->query();

        $jobs = Job::with([
            'lead' => function ($query) {
                $query->select('id', 'business_id', 'branch_id', 'status', 'lead_status', 'urgency', 'lead_stage_id')
                    ->with([
                        'business:id,name,location_id',
                        'business.location:id,name',
                        'branch:id,name',
                    ]);
            },
            'lead.services',
            'lead.stage:id,title',
            'lead.source:id,title',
        ]);

        // filters
        if ($user->hasRole('admin')) {
            if ($branch != 'all') {
                $jobs->where('branch_id', $branch);
            }
        }
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            if ($assigned_to != 'all') {
                $jobs->where('user_id', $assigned_to);
            }
        }

        if ($service != 'all') {
            $jobs->whereHas('lead.services', function ($queryBuilder) use ($query, $service) {
                $queryBuilder->where('service_id', $service);
            });
        }
        if ($classification != 'all') {
            $jobs->whereHas('lead', function ($queryBuilder) use ($query, $classification) {
                $queryBuilder->where('lead_status', $classification);
            });
        }
        if ($urgency != 'all') {
            $jobs->whereHas('lead', function ($queryBuilder) use ($query, $urgency) {
                $queryBuilder->where('urgency', $urgency);
            });
        }
        if ($status != 'all') {
            $jobs->where('status', $status);
        }
        if ($start_date != 'all' & $end_date == 'all') {
            $formatted_date = date('Y-m-d', strtotime($start_date));
            $jobs->where('start_date', $formatted_date);
        }
        if ($end_date != 'all' & $start_date == 'all') {
            $formatted_date = date('Y-m-d', strtotime($end_date));
            $jobs->where('end_date', $formatted_date);
        }
        if ($start_date != 'all' && $end_date != 'all') {
            $formatted_start_date = date('Y-m-d', strtotime($start_date));
            $formatted_end_date = date('Y-m-d', strtotime($end_date));

            $jobs->whereBetween('start_date', [$formatted_start_date, $formatted_end_date]);
        }

        if ($stage != 'all') {
            $jobs->whereHas('lead', function ($queryBuilder) use ($query, $stage) {
                $queryBuilder->where('lead_stage_id', $stage);
            });
        }


        if (!empty($query)) {
            // If a search query is provided, filter leads by business name
            $jobs->whereHas('lead.business', function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', '%' . $query . '%');
            });
        }

        if ($user->hasRole('executive')) {
            $jobs = $jobs->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $jobs = $jobs->where('branch_id', $user->managedBranch?->id);
        }

        $jobs = $jobs->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $paginationData = new CustomPaginationResource($jobs);

        if ($user->hasRole('manager') || $user->hasRole('admin')) {
            $jobs->load([
                'user',
            ]);
        }

        $jobs->getCollection()->transform(function ($job) use ($user) {
            $job->lead->services->transform(function ($service) use ($user) {
                if ($user->hasRole('executive')) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                    ];
                } else {
                    return $service;
                }
            });
            return $job;
        });
        return response()->json(['jobs' => $jobs->getCollection(), 'pagination' => $paginationData], Response::HTTP_OK);


    }

    public function allJobs(Request $request)
    {
        $user = $request->user();

        $jobs = Job::with([
            'lead' => function ($query) {
                $query->select('id', 'business_id', 'branch_id', 'status', 'lead_status', 'urgency')
                    ->with([
                        'business:id,name,location_id',
                        'business.location:id,name',
                    ]);
            },
        ])
            ->withCount(['followups'])
            ->where('status', 1)
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();
        return response()->json(['jobs' => $jobs], Response::HTTP_OK);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        // Retrieve a single lead with business and services
        $job = Job::with(
            'lead.business.location.route',
            'lead.business.contacts',
            'lead.business.photos:business_id,id,photo,type,name',
            'lead.services',
            'lead.stage:id,title',
            'lead.source:id,title',
            'lead.user.branches',
            'followups.reason',
            'closedBy:id,name',
            'user',
            'user.branches:id,name'
        )->findOrFail($id);

//        if ($user->hasRole('manager') || $user->hasRole('admin')) {
//            $job->load([
//
//            ]);
//        }

        // Modify the data to hide pivot fields for executives
        if ($user->hasRole('executive')) {
            $job->lead->services->transform(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'pivot' => [
                        'status' => $service->pivot->status,
                    ],
                ];
            });
        }

        return response()->json(['job' => $job]);
    }


    public function store(Request $request)
    {
        $user = $request->user();
        $user->load('managedBranch');

        $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'lead_id' => 'required|exists:leads,id',
            'user_id' => 'required|exists:users,id',
        ]);

        // Format start_date and end_date to MySQL date format (Y-m-d)
        $start_date = date('Y-m-d', strtotime($request->input('start_date')));
        $end_date = date('Y-m-d', strtotime($request->input('end_date')));

        // Include branch_id and formatted dates in the request data
        $data = $request->all();
        $data['branch_id'] = $user->managedBranch->id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $services = $request->input('services');
        $lead_id = $request->input('lead_id');

        foreach ($services as $serviceData) {
            $serviceId = $serviceData['id'];

            // Check if 'from' and 'to' keys exist in $serviceData
            if (array_key_exists('from', $serviceData) && array_key_exists('to', $serviceData)) {
                $priceFrom = $serviceData['from'];
                $priceTo = $serviceData['to'];
            } else {
                $priceFrom = null;
                $priceTo = null;
            }

            $lead = Lead::find($lead_id);

            $lead->services()->updateExistingPivot(
                $serviceId,
                [
                    'price_from' => $priceFrom,
                    'price_to' => $priceTo,
                ]
            );
        }


        $job = Job::create($data);
        $job->load([
            'lead.business.location',
            'user',
            'lead.services',
            'lead.stage:id,title',
            'lead.source:id,title',
        ]);

        return response()->json(['job' => $job], Response::HTTP_CREATED);
    }


    public function update(Request $request, $id)
    {
        $user = $request->user();
        $user->load('managedBranch');

        $this->validate($request, [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'lead_id' => 'required|exists:leads,id',
            'user_id' => 'required|exists:users,id',
        ]);

        // Format start_date and end_date to MySQL date format (Y-m-d)
        $start_date = date('Y-m-d', strtotime($request->input('start_date')));
        $end_date = date('Y-m-d', strtotime($request->input('end_date')));

        // Include branch_id and formatted dates in the request data
        $data = $request->all();
        $data['branch_id'] = $user->managedBranch->id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        // Find the job by ID
        $job = Job::find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }

        // Update the job with the new data
        $job->update($data);

        $services = $request->input('services');
        $lead_id = $request->input('lead_id');

        foreach ($services as $serviceData) {
            $serviceId = $serviceData['id'];
            $priceFrom = $serviceData['from']; // Assuming 'price_from' is present in the input
            $priceTo = $serviceData['to']; // Assuming 'price_to' is present in the input

            $lead = Lead::find($lead_id);

            $lead->services()->updateExistingPivot(
                $serviceId,
                [
                    'price_from' => $priceFrom,
                    'price_to' => $priceTo,
                ]
            );
        }

        // Load any necessary relationships
        $job->load([
            'lead.business.location',
            'user',
            'lead.services',
            'lead.stage:id,title',
            'lead.source:id,title',
        ]);

        return response()->json(['job' => $job], Response::HTTP_OK);
    }

    public function changeStatus(Request $request)
    {
        $user = $request->user();
        $job_id = $request->input('id');
        $status = $request->input('status');

        $job = Job::find($job_id);
        $job->status = $status;
        $job->closed_by = $user->id;
        $job->closed_date = today();

        $job->save();

        $lead = Lead::find($job->lead_id);
        $lead->status = $status;
        $lead->closed_by = $user->id;
        $lead->closed_date = today();

        $lead->save();

        return response()->json('status changed', Response::HTTP_OK);

    }


    public function destroy(Job $job)
    {
        $job->delete();

        return response()->json(['message' => 'Job deleted successfully'], Response::HTTP_OK);
    }

    public function jobFilters(Request $request)
    {
        $user = $request->user();
        $filters = [];

        if ($user->hasRole('admin')) {
            $jobs = Job::get();
        } elseif ($user->hasRole('manager')) {
            $user->load('managedBranch');
            $jobs = Job::where('branch_id', $user->managedBranch?->id)->get();
        } else {
            $jobs = Job::where('user_id', $user->id)->get();
        }

        $jobs->load([
            'lead.services',
            'user',
            'lead.branch',
            'lead.stage:id,title',
            'lead.source:id,title',
        ]);


        if ($user->hasRole('admin')) {
            // Group leads by branch ID and get unique branches
            $uniqueBranches = $jobs->groupBy('branch.id')->map(function ($group) {
                return [
                    'id' => $group[0]->branch->id,
                    'name' => $group[0]->branch->name
                ];
            })->values()->toArray();
            $filters['branches'] = $uniqueBranches;
        }

        $services = [];
        $assigned_to = [];
        $stages = [];
        foreach ($jobs as $job) {
            foreach ($job->lead->services as $service) {
                $services[] = [
                    "id" => $service->id,
                    "name" => $service->name
                ];
            }
            $assigned_to[] = [
                'id' => $job->user->id,
                'name' => $job->user->name,
                'branch_id' => $job->branch_id
            ];
            $stages[] = [
                "id" => $job->lead->stage->id,
                "name" => $job->lead->stage->title
            ];

        }

        $uniqueServices = collect($services)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        $uniqueAssignedTo = collect($assigned_to)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"],
                "branch_id" => $group[0]["branch_id"]
            ];
        })->values()->toArray();

        $uniqueStages = collect($stages)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        $filters['services'] = $uniqueServices;
        $filters['stages'] = $uniqueStages;
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            $filters['assigned_to'] = $uniqueAssignedTo;
        }

        return response()->json(['filters' => $filters], 200);


    }

    public function uploadDocuments(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $id = $request->input('id');
        $file = $request->file('file');
        $type = $request->input('type');

        $mimes = ['jpeg', 'png', 'pdf', 'doc', 'docx'];

//         If there are uploaded files
        if (!empty($file)) {
                // Get the original filename
                $originalFilename = $file->getClientOriginalName();

                // Extract the file extension
                $extension = $file->getClientOriginalExtension();

                // Generate a unique filename with a timestamp before the extension
                $timestamp = now()->format('YmdHis'); // Format the timestamp as desired
                $newFilename = pathinfo($originalFilename, PATHINFO_FILENAME) . '_' . $timestamp . '.' . $extension;

                $mime_type = $file->getClientMimeType();

                // Validate and store the file with the new filename
                $file->storeAs('uploads/jobs/documents/' . $id, $newFilename);

                // Create a new ExecutiveDocument record
                JobDocuments::create([
                    'user_id' => $user->id,
                    'job_id' => $id,
                    'type_id' => $type,
                    'mime_type' => $mimes[$mime_type] ?? 'docx',
                    'file_name' => $newFilename
                ]);

            return response()->json("Uploaded", 201);
        }

        return response()->json("No files uploaded", 200);
    }
    public function getDocuments(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $job = Job::with(['documents' => function ($query) {
            $query->orderBy('id', 'desc');
        }])->find($id);

        $job->load(['documents.type']);
        return response()->json($job->documents, 200);
    }
    public function deleteDocument(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $document = JobDocuments::find($id);
        if ($document) {
            // Get the file path
            $filePath = 'uploads/jobs/documents/' . $document->job_id . '/' . $document->file_name;

            // Check if the file exists in the storage
            if (Storage::exists($filePath)) {
                // Delete the file from the storage
                Storage::delete($filePath);
            }

            // Delete the document record from the database
            $document->delete();

            return response()->json("Document and file deleted", 200);
        }

        // If the document does not exist
        return response()->json("Document not found", 404);
    }

    public function exportJobs(Request $request)
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
        return Excel::download(new JobExport($parameters, $userRole, $userId, $userBranchId), 'jobs.xlsx');
    }
}
