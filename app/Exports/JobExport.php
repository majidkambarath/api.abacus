<?php

namespace App\Exports;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Job;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class JobExport implements FromView, ShouldAutoSize, WithStyles
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

        $service = $this->parameters['service'];
        $classification = $this->parameters['classification'];
        $urgency = $this->parameters['urgency'];
        $status = $this->parameters['status'];
        $start_date = $this->parameters['start_date'];
        $end_date = $this->parameters['end_date'];

        $stage = $this->parameters['stage'];
        $q = $this->parameters['q'];

        $jobs = Job::with([
            'lead',
            'lead.business.contacts',
            'lead.business.location',
            'lead.services',
            'lead.stage',
            'lead.source',
            'lead.user',
            'user',
        ]);
        if ($this->userRole == 'admin') {
            $branch = $this->parameters['branch'];
            if ($branch != 'all') {
                $jobs->where('branch_id', $branch);
            }
        }
        if ($this->userRole == 'admin' || $this->userRole == 'manager') {
            $assigned_to = $this->parameters['assigned_to'];

            if ($assigned_to != 'all') {
                $jobs->where('user_id', $assigned_to);
            }
        }

        if ($service != 'all') {
            $jobs->whereHas('lead.services', function ($queryBuilder) use ($service) {
                $queryBuilder->where('service_id', $service);
            });
        }
        if ($classification != 'all') {
            $jobs->whereHas('lead', function ($queryBuilder) use ($classification) {
                $queryBuilder->where('lead_status', $classification);
            });
        }
        if ($urgency != 'all') {
            $jobs->whereHas('lead', function ($queryBuilder) use ($urgency) {
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
            $jobs->whereHas('lead', function ($queryBuilder) use ($stage) {
                $queryBuilder->where('lead_stage_id', $stage);
            });
        }


        if (!empty($q)) {
            // If a search query is provided, filter leads by business name
            $jobs->whereHas('lead.business', function ($queryBuilder) use ($q) {
                $queryBuilder->where('name', 'like', '%' . $q . '%');
            });
        }

        if ($this->userRole == 'executive') {
            $jobs = $jobs->where('user_id', $this->userId);
        } elseif ($this->userRole == 'manager') {
            $jobs = $jobs->where('branch_id', $this->userBranchId);
        }

        $jobs = $jobs->orderBy('id', 'desc')->get();
        return view('exports.excel.jobs', [
            'jobs' => $jobs,
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
