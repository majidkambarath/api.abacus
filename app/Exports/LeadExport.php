<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class LeadExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $parameters;
    protected $userRole;
    protected $userId;
    protected $userBranchId;
    public function __construct($parameters, $userRole, $userId, $userBranchId)
    {
        $this->parameters = $parameters;
        $this->userRole = $userRole;
        $this->userId = $userId;
        $this->userBranchId = $userBranchId;
    }
    public function view(): \Illuminate\Contracts\View\View
    {
        $q = $this->parameters['q'];
        $service = $this->parameters['service'];
        $route = $this->parameters['route'];
        $location = $this->parameters['location'];
        $stage = $this->parameters['stage'];
        $source = $this->parameters['source'];
        $classification = $this->parameters['classification'];
        $urgency = $this->parameters['urgency'];
        $status = $this->parameters['status'];
        $start_date = $this->parameters['start_date'];
        $end_date = $this->parameters['end_date'];

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
        if (!empty($q)) {
            $leads->whereHas('business', function ($queryBuilder) use ($q) {
                $queryBuilder->where('name', 'like', '%' . $q . '%');
            });
        }

        if ($this->userRole == 'admin') {
            $branch = $this->parameters['branch'];
            if ($branch != 'all') {
                $leads->where('branch_id', $branch);
            }
        }
        if ($this->userRole == 'admin' || $this->userRole == 'manager') {
            $created_by = $this->parameters['created_by'];
            $assigned_to = $this->parameters['assigned_to'];

            if ($created_by != 'all') {
                $leads->where('user_id', $created_by);
            }
            if ($assigned_to != 'all' && $assigned_to != 'unassigned') {
                $leads->whereHas('job', function ($queryBuilder) use ($assigned_to) {
                    $queryBuilder->where('user_id', $assigned_to);
                });
            }
            if ($assigned_to == 'unassigned') {
                $leads->whereDoesntHave('job');
            }
        }

        if ($service != 'all') {
            $leads->whereHas('services', function ($queryBuilder) use ($service) {
                $queryBuilder->where('service_id', $service);
            });
        }
        if ($route != 'all') {
            $leads->whereHas('business.location.route', function ($queryBuilder) use ($route) {
                $queryBuilder->where('id', $route);
            });
        }
        if ($location != 'all') {
            $leads->whereHas('business.location', function ($queryBuilder) use ($location) {
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

            $leads->whereBetween('created_at', [$formatted_start_date, $formatted_end_date]);
        }

        if ($this->userRole == 'executive') {
            $leads = $leads->where('user_id', $this->userId);
        } elseif ($this->userRole == 'manager'){
            $leads = $leads->where('branch_id', $this->userBranchId);
        }

        $leads = $leads->orderBy('id', 'desc')->get();
        return view('exports.excel.leads', [
            'leads' => $leads,
            'user_role' => $this->userRole
        ]);
    }
    public function styles(Worksheet $sheet)
    {
        // Add a black border around all cells
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'], // Black color
                ],
            ],
        ];

        // Apply the style to all cells
        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray($styleArray);
    }
}
