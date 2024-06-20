<?php

namespace App\Exports;

use App\Http\Resources\CustomPaginationResource;
use App\Models\Followup;
use App\Models\Job;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class FollowupExport implements FromView, ShouldAutoSize, WithStyles
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

        $start_date = $this->parameters['start_date'];
        $end_date = $this->parameters['end_date'];
        $job_id = $this->parameters['job_id'];

        $stage = $this->parameters['stage'];
        $q = $this->parameters['q'];

        $followups = Followup::with([
            'lead',
            'lead.business.contacts',
            'lead.business.location',
            'lead.services',
            'lead.stage',
            'lead.source',
            'lead.user',
            'user',
            'job',
            'job.user',
            'reason:id,title',
        ]);

        if ($job_id && $job_id != 'all') {
            $followups->where('job_id', $job_id);
        }
        if ($this->userRole == 'admin') {
            $branch = $this->parameters['branch'];
            if ($branch != 'all') {
                $followups->where('branch_id', $branch);
            }
        }
        if ($stage != 'all') {
            $followups->whereHas('lead', function ($queryBuilder) use ($stage) {
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
        if ($this->userRole == 'executive') {
            $followups = $followups->where('user_id', $this->userId);
        } elseif ($this->userRole == 'manager') {
            $followups = $followups->where('branch_id', $this->userBranchId);
        }
        if (!empty($q)) {
            // If a search query is provided, filter leads by business name
            $followups->whereHas('lead.business', function ($queryBuilder) use ($q) {
                $queryBuilder->where('name', 'like', '%' . $q . '%');
            });
        }

        $followups = $followups->orderBy('id', 'desc')->get();
        return view('exports.excel.followups', [
            'followups' => $followups,
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
