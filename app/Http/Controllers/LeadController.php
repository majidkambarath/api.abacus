<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Business;
use App\Models\BusinessPhoto;
use App\Models\Lead;
use App\Models\LeadStageHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Exports\LeadExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
class LeadController extends Controller
{
    public function index(Request $request)
    {

        $query = request()->input('q');
        $branch = request()->input('branch');
        $created_by = request()->input('created_by');
        $assigned_to = request()->input('assigned_to');
        $service = request()->input('service');
        $classification = request()->input('classification');
        $urgency = request()->input('urgency');
        $status = request()->input('status');
        $stage = request()->input('stage');
        $source = request()->input('source');

        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');

        $route = request()->input('route');
        $location = request()->input('location');

        $perPage = request()->input('perPage', 5);
        $page = request()->input('page', 1);

        $user = $request->user();


        $leads = Lead::with(
            'business.location',
            'business.contacts',
            'services',
            'user',
            'branch:id,name',
            'job.user',
            'stage:id,title',
            'source:id,title',
        );

        if (!empty($query)) {
            // If a search query is provided, filter leads by business name
            $leads->whereHas('business', function ($queryBuilder) use ($query) {
                $queryBuilder->where('name', 'like', '%' . $query . '%');
            });
        }
        if ($user->hasRole('admin')) {
            if ($branch != 'all') {
                $leads->where('branch_id', $branch);
            }
        }
        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            if ($created_by != 'all') {
                $leads->where('user_id', $created_by);
            }
            if ($assigned_to != 'all' && $assigned_to != 'unassigned') {
                $leads->whereHas('job', function ($queryBuilder) use ($query, $assigned_to) {
                    $queryBuilder->where('user_id', $assigned_to);
                });
            }
            if ($assigned_to == 'unassigned') {
                $leads->whereDoesntHave('job');
            }
        }
        if ($service != 'all') {
            $leads->whereHas('services', function ($queryBuilder) use ($query, $service) {
                $queryBuilder->where('service_id', $service);
            });
        }
        if ($route != 'all') {
            $leads->whereHas('business.location.route', function ($queryBuilder) use ($query, $route) {
                $queryBuilder->where('id', $route);
            });
        }
        if ($location != 'all') {
            $leads->whereHas('business.location', function ($queryBuilder) use ($query, $location) {
                $queryBuilder->where('id', $location);
            });
        }
        if ($stage != 'all') {
            $leads->where('lead_stage_id', $stage);
        }
        if ($source != 'all') {
            $leads->where('lead_source_id', $source);
        }
        if ($classification != 'all') {
            $leads->where('lead_status', $classification);
        }
        if ($urgency != 'all') {
            $leads->where('urgency', $urgency);
        }
        if ($status != 'all') {
            $leads->where('status', $status);
        }
        if ($status == 'all') {
            $leads->whereNot('status', 3);
        }

        if ($start_date != 'all' & $end_date == 'all') {
            $formatted_date = date('Y-m-d', strtotime($start_date));
            $leads->whereDate('created_at', $formatted_date);
        }
        if ($end_date != 'all' & $start_date == 'all') {
            $formatted_date = date('Y-m-d', strtotime($end_date));
            $leads->whereDate('created_at', $formatted_date);
        }
        if ($start_date != 'all' && $end_date != 'all') {
            $formatted_start_date = date('Y-m-d', strtotime($start_date));
            $formatted_end_date = date('Y-m-d', strtotime($end_date));

            $leads->whereDate('created_at', '>=', $formatted_start_date)
                ->whereDate('created_at', '<=', $formatted_end_date);
        }

        if ($user->hasRole('executive')) {
            // If the user is an executive, modify the services collection within each lead
            $leads = $leads->where('user_id', $user->id)
                ->withCount('job')
                ->orderBy('id', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $leads->getCollection()->transform(function ($lead) {
                $lead->services->transform(function ($service) {
                    // Create a new service object with only the 'status' field
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'pivot' => [
                            'status' => $service->pivot->status,
                        ],
                    ];
                });
                return $lead;
            });

        } elseif ($user->hasRole('manager')) {
            $user->load('managedBranch');
            $leads = $leads->where('branch_id', $user->managedBranch?->id)->withCount('job')->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        }

        if ($user->hasRole('admin')) {
            $leads = $leads->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        }

        $paginationData = new CustomPaginationResource($leads);

        return response()->json(['leads' => $leads->getCollection(), 'pagination' => $paginationData]);
    }

    public function allLeads(Request $request)
    {
        $user = $request->user();
        $user->load('managedBranch');
        $leads = Lead::select('id', 'status', 'business_id')->with(
            'services',
            'business:id,name,location_id',
            'business.location:id,name',
            'stage:id,title',
            'source:id,title',
        )->where('branch_id', $user->managedBranch?->id)
            ->where('status', 1)
            ->whereDoesntHave('job')
            ->orderBy('id', 'desc')->get();

        return response()->json(['leads' => $leads]);

    }


    public function show($id, Request $request)
    {
        $user = $request->user();

        // Retrieve a single lead with business and services
        $lead = Lead::with(
            'business.location.route',
            'business.contacts',
            'business.photos:business_id,id,photo,type,name',
            'services',
            'stage:id,title',
            'source:id,title',
            'user.branches:id,name',
        )->findOrFail($id);

        if ($user->hasRole('executive')) {
            // If the user is an executive, modify the services collection
            $lead->services->transform(function ($service) {
                // Create a new service object with only the 'status' field
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'pivot' => [
                        'status' => $service->pivot->status,
                    ],
                ];
            });
        } else {
            if ($user->hasRole('manager') || $user->hasRole('admin')) {
                $lead->load([
                    'job.user',
                    'job.followups.reason',
                    'job.closedBy:id,name',
                ]);
            }
        }

        return response()->json(['lead' => $lead]);
    }


    public function store(Request $request)
    {
        $user = $request->user();


        if($user->hasRole('manager')){
            $user->load('managedBranch');
            $branch_id = $user->managedBranch?->id;
        } else {
            $branch_id = $user->branches[0]->id;
        }

        $lead = new Lead([
            'lead_status' => $request->input('lead_status'),
            'urgency' => $request->input('urgency'),
            'note' => $request->input('note'),
            'lead_source_id' => $request->input('lead_source_id'),
            'lead_stage_id' => 1,
            'user_id' => $user->id,
            'status' => 1,
            'branch_id' => $branch_id
        ]);

        $business = new Business([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'landphone' => $request->input('landphone'),
            'location_url' => $request->input('location_url'),
            'location_id' => $request->input('location'),
        ]);
        $business->save();


        if ($request->has('contact_name') || $request->has('contact_phone')) {
            $business->contacts()->create([
                'name' => $request->input('contact_name'),
                'phone_number' => $request->input('contact_phone')
            ]);
        }

        $lead->business_id = $business->id;

        $lead->save();
        $lead->services()->sync($request->input('selectedServices'));

        $leadStageHistory = new LeadStageHistory([
            'lead_id' => $lead->id,
            'stage_id' => 1,
            'user_id' => $user->id,
        ]);

        $leadStageHistory->save();

        $lead->load(['business.location', 'business.contacts', 'services']);

        return response()->json(['message' => 'Lead created successfully', 'lead' => $lead], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        $lead->lead_status = $request->input('lead_status');
        $lead->urgency = $request->input('urgency');
        $lead->note = $request->input('note');
        $lead->user_id = $user->id;
        $lead->lead_source_id = $request->input('lead_source_id');

        $lead->save();

        $business = $lead->business;
        $business->name = $request->input('name');
        $business->email = $request->input('email');
        $business->address = $request->input('address');
        $business->landphone = $request->input('landphone');
        $business->location_url = $request->input('location_url');
        $business->location_id = $request->input('location');
        $business->save();

        // Update the contact
        $contact = $business->contacts->first();
        if ($contact) {
            $contact->name = $request->input('contact_name');
            $contact->phone_number = $request->input('contact_phone');
            $contact->save();
        }

        // Update services
        $selectedServices = $request->input('selectedServices', []);
        $lead->services()->sync($selectedServices);

        $lead->load(['business.location', 'business.contacts', 'services']);

        return response()->json(['message' => 'Lead updated successfully', 'lead' => $lead], 200);
    }

    public function destroy($id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $lead->services()->detach();
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }
    public function changeStatus($id): JsonResponse
    {
        $status = request()->input('status');
        $lead = Lead::findOrFail($id);

        if($status === 'hold') {
            $lead->status = 3;
        }
        elseif($status === 'open') {
            $lead->status = 1;
        }
        $lead->save();

        return response()->json(['message' => 'Lead updated successfully']);
    }

    public function checkBusiness($number)
    {
        // Assuming 'Business' is your model name
        $business = Business::where('landphone', $number)->first();

        if ($business) {
            $business->load(['location', 'contacts']);
            // Business with matching landphone number found
            return response()->json(['business' => $business]);
        } else {
            // No matching business found
            return response()->json(['message' => 'Business not found'], 422);
        }
    }

    public function updateService(Request $request, $id)
    {
        $priceFrom = $request->input('price_from'); // Assuming 'price_from' is present in the input
        $priceTo = $request->input('price_to');
        $status = $request->input('status');
        $incentiveAmount = $request->input('incentive_amount');
        $incentiveType = $request->input('incentive_type');
        $serviceId = $request->input('service_id');

        $lead = Lead::find($id);
        $lead->services()->updateExistingPivot(
            $serviceId,
            [
                'price_from' => $priceFrom,
                'price_to' => $priceTo,
                'incentive_amount' => $incentiveAmount,
                'incentive_type' => $incentiveType,
                'status' => $status,
            ]
        );
//        $lead->load(['business.location', 'business.contacts', 'services']);
        return response()->json(['message' => 'Lead updated successfully'], 200);
    }

    public function LeadFilters(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $leads = Lead::get();
        } elseif ($user->hasRole('manager')) {
            $user->load('managedBranch');
            $leads = Lead::where('branch_id', $user->managedBranch?->id)->get();
        } else {
            $leads = Lead::where('user_id', $user->id)->get();
        }

        $leads->load([
            'job.user',
            'branch',
            'services',
            'user',
            'stage:id,title',
            'source:id,title',
            'business.location.route']);

        $services = [];
        $created_by = [];
        $assigned_to = [];
        $stages = [];
        $sources = [];
        $routes = [];
        $locations = [];
        foreach ($leads as $lead) {
            foreach ($lead->services as $service) {
                $services[] = [
                    "id" => $service->id,
                    "name" => $service->name
                ];
            }
            $stages[] = [
                "id" => $lead->stage->id,
                "name" => $lead->stage->title
            ];
            if($lead->lead_source_id){
                $sources[] = [
                    "id" => $lead->source->id,
                    "name" => $lead->source->title
                ];
            }
            $routes[] = [
                "id" => $lead->business->location->route->id,
                "name" => $lead->business->location->route->name
            ];
            $locations[] = [
                "id" => $lead->business->location->id,
                "name" => $lead->business->location->name,
                "route_id" => $lead->business->location->route->id
            ];
            $created_by[] = [
                'id' => $lead->user->id,
                'name' => $lead->user->name,
                'branch_id' => $lead->branch_id,
            ];
            if ($lead->job) {
                $assigned_to[] = [
                    'id' => $lead->job->user->id,
                    'name' => $lead->job->user->name,
                    'branch_id' => $lead->branch_id,
                ];
            }

        }

        $uniqueServices = collect($services)->groupBy('id')->map(function ($group) {
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

        $uniqueSources = collect($sources)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        $uniqueRoutes = collect($routes)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"]
            ];
        })->values()->toArray();

        $uniqueLocations = collect($locations)->groupBy('id')->map(function ($group) {
            return [
                "id" => $group[0]["id"],
                "name" => $group[0]["name"],
                "route_id" => $group[0]["route_id"],
            ];
        })->values()->toArray();

        $filters['services'] =  $uniqueServices;
        $filters['stages'] =  $uniqueStages;
        $filters['routes'] =  $uniqueRoutes;
        $filters['locations'] =  $uniqueLocations;
        $filters['sources'] =  $uniqueSources;


        if ($user->hasRole('admin')) {
            // Group leads by branch ID and get unique branches
            $uniqueBranches = $leads->groupBy('branch.id')->map(function ($group) {
                return [
                    'id' => $group[0]->branch->id,
                    'name' => $group[0]->branch->name
                ];
            })->values()->toArray();
            $filters['branches'] = $uniqueBranches;
        }
        if ($user->hasRole('admin') || $user->hasRole('manager')) {

            $uniqueCreatedBy = collect($created_by)->groupBy('id')->map(function ($group) {
                return [
                    "id" => $group[0]["id"],
                    "name" => $group[0]["name"],
                    "branch_id" => $group[0]["branch_id"],
                ];
            })->values()->toArray();

            $uniqueAssignedTo = collect($assigned_to)->groupBy('id')->map(function ($group) {
                return [
                    "id" => $group[0]["id"],
                    "name" => $group[0]["name"],
                    "branch_id" => $group[0]["branch_id"],
                ];
            })->values()->toArray();

            $filters['created_by'] =  $uniqueCreatedBy;
            $filters['assigned_to'] =  $uniqueAssignedTo;
        }

        return response()->json(['filters' => $filters], 200);
    }

    public function uploadFile(Request $request)
    {
        $lead_id = $request->input('id');
        $type = $request->input('type');
        $files = $request->allFiles();

        $lead = Lead::find($lead_id);

        // If there are uploaded files
        if (!empty($files)) {
            foreach ($files as $file) {
                // Get the original filename
                $originalFilename = $file->getClientOriginalName();

                // Extract the file extension
                $extension = $file->getClientOriginalExtension();

                // Generate a unique filename with a timestamp before the extension
                $timestamp = now()->format('YmdHis'); // Format the timestamp as desired
                $newFilename = $type . '_' . $timestamp . '.' . $extension;

                $mime_type = $file->getClientMimeType();

                // Validate and store the file with the new filename
                $path = $file->storeAs('uploads/photos/business/' . $lead->business_id, $newFilename);

                // Create a new ExecutiveDocument record
                BusinessPhoto::create([
                    'business_id' => $lead->business_id, // Associate with the correct executive
                    'photo' => $path,
                    'type' => $type,
                    'name' => $newFilename
                ]);
            }

            return response()->json("Files processed successfully.", 201);
        }

        return response()->json("No files uploaded.", 200);
    }

    // change stage of lead
    public function changeStage($id, Request $request): JsonResponse
    {
        $stage = request()->input('stage');
        $lead = Lead::findOrFail($id);

        $lead->lead_stage_id = $stage;
        $lead->save();
        $user = $request->user();

        $leadStageHistory = new LeadStageHistory([
            'lead_id' => $id,
            'stage_id' => $stage,
            'user_id' => $user->id,
        ]);

        $leadStageHistory->save();

        return response()->json(['message' => 'Lead updated successfully']);
    }

    public function getLeadStageHistory($id): JsonResponse
    {
        $history = LeadStageHistory::where('lead_id', $id)->with(['stage:id,title', 'user:id,name'])->orderBy('id', 'desc')->get();

        return response()->json(['history' => $history]);
    }

    public function exportLeads(Request $request)
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
        return Excel::download(new LeadExport($parameters, $userRole, $userId, $userBranchId), 'leads.xlsx');
    }
    public function generateDailyReport(Request $request)
    {
        $start_date = request()->input('start_date');
        $end_date = request()->input('end_date');
        $user = $request->user();

        $leads = Lead::with(
            'business.location',
            'business.contacts',
            'services',
            'user',
            'branch:id,name',
            'job.user',
            'stage:id,title',
            'source:id,title',
        );

        if ($start_date != 'all' && $end_date == 'all') {
            $formatted_date = date('Y-m-d', strtotime($start_date));
            $leads->whereDate('created_at', $formatted_date);
        }
        if ($start_date != 'all' && $end_date != 'all') {
            $formatted_start_date = date('Y-m-d', strtotime($start_date));
            $formatted_end_date = date('Y-m-d', strtotime($end_date));

            $leads->whereDate('created_at', '>=', $formatted_start_date)
                ->whereDate('created_at', '<=', $formatted_end_date);
        }

        $leads = $leads->where('user_id', $user->id)->orderBy('id', 'desc')->get()->toArray();
//        return response()->json(['history' => $leads]);

        // You can access the user role using $user->role
        $pdf = Pdf::loadView('exports.pdf.daily_reports', ["leads" => $leads, "user" => $user->toArray()])->setPaper('a4', 'landscape');
//
        return $pdf->download('daily_reports.pdf');
    }



}
