<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Followup;
use App\Models\Job;
use App\Models\Lead;
use App\Models\Route;
use App\Models\Service;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function leadsAndJobs(Request $request)
    {
        $user = $request->user();
        $user->load('managedBranch');
        $branchId = request('branch');

        $year = date('Y'); // Change this to the desired year

        // Function to get counts for a specific model (Lead or Job)
        $getCounts = function ($model, $status) use ($year, $user, $branchId) {
            $data = $model::whereYear('created_at', $year);
            if($status === 0){
                $data = $data->where('status', $status);
            }
            if ($user->hasRole('manager')) {
                $data = $data->where('branch_id', $user->managedBranch?->id);
            }
            if($branchId !== 'all') {
                $data = $data->where('branch_id', $branchId);
            }
            $data = $data
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupByRaw('MONTH(created_at)')
                ->orderByRaw('MONTH(created_at)')
                ->pluck('count', 'month')
                ->toArray();
            return $data;

        };

        // Get lead and job counts
        $leadCounts = $getCounts(Lead::class, null);
        $jobCounts = $getCounts(Job::class, 0);

        // Create arrays with default values for all months
        $allMonths = range(1, 12);
        $leadCountsArray = array_map(function ($month) use ($leadCounts) {
            return $leadCounts[$month] ?? 0;
        }, $allMonths);

        $jobCountsArray = array_map(function ($month) use ($jobCounts) {
            return $jobCounts[$month] ?? 0;
        }, $allMonths);

        $leadChart = [
            'leads' => $leadCountsArray,
            'jobs' => $jobCountsArray,
        ];

        if ($user->hasRole('manager')) {
            $total_leads = Lead::where('branch_id', $user->managedBranch?->id)->count();
            $total_jobs = Job::where('branch_id', $user->managedBranch?->id)->count();
            $total_jobs_closed = Job::where('branch_id', $user->managedBranch?->id)->where('status', 0)->count();

            // cold warm hot
            $cold_leads = Lead::where('branch_id', $user->managedBranch?->id)->where('lead_status', 0)->count();
            $warm_leads = Lead::where('branch_id', $user->managedBranch?->id)->where('lead_status', 1)->count();
            $hot_leads = Lead::where('branch_id', $user->managedBranch?->id)->where('lead_status', 2)->count();

        } elseif($user->hasRole('admin')) {
            if($branchId !== 'all') {
                $total_leads = Lead::where('branch_id', $branchId)->count();
                $total_jobs = Job::where('branch_id', $branchId)->count();
                $total_jobs_closed = Job::where('branch_id', $branchId)->count();

                // col warm hot
                $cold_leads = Lead::where('lead_status', 0)->where('branch_id', $branchId)->count();
                $warm_leads = Lead::where('lead_status', 1)->where('branch_id', $branchId)->count();
                $hot_leads = Lead::where('lead_status', 2)->where('branch_id', $branchId)->count();
            } else {
                $total_leads = Lead::count();
                $total_jobs = Job::count();
                $total_jobs_closed = Job::where('status', 0)->count();

                // col warm hot
                $cold_leads = Lead::where('lead_status', 0)->count();
                $warm_leads = Lead::where('lead_status', 1)->count();
                $hot_leads = Lead::where('lead_status', 2)->count();
            }

        }


        $total = [
            'leads' => $total_leads,
            'jobs' => $total_jobs,
            'closed_jobs' => $total_jobs_closed
        ];

        $classifications = [
            'hot' => $hot_leads,
            'warm' => $warm_leads,
            'cold' => $cold_leads,
        ];

        $route_leads = [];

        if ($user->hasRole('manager')) {
            $services = Service::withCount(['leads' => function ($query) use ($user) {
                $query->where('branch_id', $user->managedBranch?->id);
            }])
                ->orderByDesc('leads_count')
                ->get(['name', 'leads_count']);


            $route_leads = Lead::where('branch_id', $user->managedBranch?->id)
                ->with(['business.location.route'])
                ->get()
                ->pluck('business.location');


        } elseif($user->hasRole('admin')) {
            if($branchId !== 'all') {
                $services = Service::withCount(['leads' => function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                }])
                    ->orderByDesc('leads_count')
                    ->get(['name', 'leads_count']);
                $route_leads = Lead::where('branch_id', $branchId)
                    ->with(['business.location.route'])
                    ->get()
                    ->pluck('business.location');
            } else {
                $services = Service::withCount(['leads'])
                    ->orderByDesc('leads_count')
                    ->get(['name', 'leads_count']);
                $route_leads = Lead::with(['business.location.route'])
                    ->get()
                    ->pluck('business.location');
            }
        }



        $services_result = $services->map(function ($service) {
            return [
                'name' => $service->name,
                'count' => $service->leads_count,
            ];
        });

        return response()->json(['report' => [
            'lead_chart' => $leadChart,
            'total' => $total,
            'classifications' => $classifications,
            'services' => $services_result,
            'business' => $this->getBusinessData($user, $branchId),
            'branches' => $user->hasRole('admin') ? $this->getBranchLeads() : [],
            'routes' => $this->transformRoutes($route_leads)
        ] ]);
    }


    private function transformRoutes($route_leads) {

        $transformedArray = [];

// Loop through each lead and aggregate data
        foreach ($route_leads as $lead) {
            $routeName = $lead['route']['name'];
            $locationName = $lead['name'];

            // Check if the route_name exists in the transformed array
            $key = array_search($routeName, array_column($transformedArray, 'route_name'));

            if ($key === false) {
                // If the route_name doesn't exist, create a new entry
                $transformedArray[] = [
                    'route_name' => $routeName,
                    'total' => 1,
                    'locations' => [
                        [
                            'name' => $locationName,
                            'count' => 1
                        ]
                    ]
                ];
            } else {
                // If the route_name exists, update the count or add a new location
                $locationKey = array_search($locationName, array_column($transformedArray[$key]['locations'], 'name'));

                if ($locationKey === false) {
                    // If the location doesn't exist, add a new entry
                    $transformedArray[$key]['locations'][] = [
                        'name' => $locationName,
                        'count' => 1
                    ];
                } else {
                    // If the location exists, update the count
                    $transformedArray[$key]['locations'][$locationKey]['count']++;
                }

                $transformedArray[$key]['total']++;
            }
        }

        return $transformedArray;


    }

    private function getBusinessData($user, $branchId) {


        $leads = Lead::select('id', 'business_id', 'created_at');

        if ($user->hasRole('manager')) {
            $leads = $leads->where('branch_id', $user->managedBranch?->id);
        }
        if($branchId !== 'all') {
            $leads = $leads->where('branch_id', $branchId);
        }

        $leads = $leads->get();

        $leads->load([
            'business:id,name,location_id,landphone',
            'business.location:id,name',
            'services:id,name'
        ]);

        return $this->transformBusinessData($leads);


    }
    private function transformBusinessData($data)
    {
        $result = [];

        // Create an associative array to group data by landphone number
        $businessesByLandphone = [];

        foreach ($data as $item) {
            $lead_chart = array_fill(1, 12, 0);
            $landphone = $item['business']['landphone'];

            if (!isset($businessesByLandphone[$landphone])) {
                // Initialize the business data if it doesn't exist
                $businessesByLandphone[$landphone] = [
                    'business' => $item['business']['name'],
                    'location' => $item['business']['location']['name'],
                    'total_leads' => 0,
                    'total_services' => 0,
                    'services_chart' => [],
                    'lead_chart' => $lead_chart
                ];
            }

            // Increment the total leads
            $businessesByLandphone[$landphone]['total_leads']++;

            $dateTime = new DateTime($item['created_at']);
            $monthNumber = $dateTime->format('n');
            $businessesByLandphone[$landphone]['lead_chart'][$monthNumber]++;


            // Iterate through services and increment total services
            foreach ($item['services'] as $service) {
                $serviceName = $service['name'];
                $businessesByLandphone[$landphone]['total_services']++;

                // Check if the service exists in the services_chart
                $foundServiceIndex = null;
                foreach ($businessesByLandphone[$landphone]['services_chart'] as $index => $chartItem) {
                    if ($chartItem['name'] === $serviceName) {
                        $foundServiceIndex = $index;
                        break;
                    }
                }

                if ($foundServiceIndex !== null) {
                    // Increment the total for an existing service
                    $businessesByLandphone[$landphone]['services_chart'][$foundServiceIndex]['total']++;
                } else {
                    // Add a new service to the services_chart
                    $businessesByLandphone[$landphone]['services_chart'][] = [
                        'name' => $serviceName,
                        'total' => 1,
                    ];
                }
            }
        }

        // Convert the associative array back to a sequential array
        $result = array_values($businessesByLandphone);

        usort($result, function ($a, $b) {
            return $b['total_leads'] - $a['total_leads'];
        });
        return $result;
    }

    private function getBranchLeads(){
        $leads = Lead::with('branch')->get();
        $result = [];
        foreach ($leads as $lead) {
            $branchName = $lead->branch->name;

            // Check if the branch name already exists in the result array
            $branchIndex = array_search($branchName, array_column($result, 'name'));

            if ($branchIndex !== false) {
                // Branch already exists in the result array, update the leads array
                $dateTime = $lead->created_at;
                $monthNumber = $dateTime->format('n');
                $result[$branchIndex]['leads'][$monthNumber]++; // Increment month number by 1
            } else {
                // Branch doesn't exist in the result array, create a new branch entry
                $leadsCount = array_fill(1, 12, 0); // Initialize the leads count array with 12 zeros starting from 1

                $dateTime = $lead->created_at;
                $monthNumber = $dateTime->format('n');
                $leadsCount[$monthNumber]++; // Increment month number by 1

                $result[] = [
                    'name' => $branchName,
                    'leads' => $leadsCount,
                ];
            }
        }
        return $result;
    }

}
