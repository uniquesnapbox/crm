<?php

namespace App\DataTables;

use App\Services\LeadPerformanceReportService;
use Yajra\DataTables\Html\Button;

class EmployeeLeadReportDataTable extends BaseDataTable
{
    public function __construct(private readonly LeadPerformanceReportService $reportService)
    {
        parent::__construct();
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('employee_name', fn ($row) => e($row->employee_name))
            ->editColumn('leads_contacted', fn ($row) => (int) $row->leads_contacted)
            ->editColumn('status_changed', fn ($row) => (int) $row->status_changed)
            ->editColumn('followups', fn ($row) => (int) $row->followups)
            ->editColumn('detail_changes', fn ($row) => (int) $row->detail_changes)
            ->editColumn('followup_actions', fn ($row) => (int) $row->followup_actions)
            ->editColumn('followup_updated_leads', fn ($row) => (int) $row->followup_updated_leads)
            ->editColumn('followup_status_changes', fn ($row) => (int) $row->followup_status_changes)
            ->editColumn('note_actions', fn ($row) => (int) $row->note_actions)
            ->editColumn('worked_leads', fn ($row) => (int) $row->worked_leads)
            ->addColumn('view', function ($row) {
                $url = route('lead-performance-report.employee.status_changes', ['employee' => $row->employee_id], false);

                return '<button type="button" class="btn btn-sm btn-outline-primary js-view-status-changes" '
                    . 'data-url="' . e($url) . '" title="View employee activity">View</button>';
            })
            ->addIndexColumn()
            ->rawColumns(['view']);
    }

    public function query()
    {
        return $this->reportService->employeeLeadDataQuery($this->request());
    }

    public function html()
    {
        $dataTable = $this->setBuilder('employee-lead-report-table', 1)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["employee-lead-report-table"].buttons().container().appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function() {
                    $(".select-picker").selectpicker();
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(
                Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')])
            );
        }

        return $dataTable;
    }

    protected function getColumns()
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            'Employee' => ['data' => 'employee_name', 'name' => 'users.name', 'title' => 'Employee'],
            'Leads Worked' => ['data' => 'worked_leads', 'name' => 'worked_leads', 'title' => 'Leads Worked (Unique)'],
            'Leads Updated' => ['data' => 'status_changed', 'name' => 'status_changed', 'title' => 'Lead Details Updated (Unique)'],
            'Detail Changes' => ['data' => 'detail_changes', 'name' => 'detail_changes', 'title' => 'Lead Field Changes'],
            'Follow-up Updated Leads' => ['data' => 'followup_updated_leads', 'name' => 'followup_updated_leads', 'title' => 'Follow-up Updated Leads (Unique)'],
            'Follow-up Status Changes' => ['data' => 'followup_status_changes', 'name' => 'followup_status_changes', 'title' => 'Follow-up Status Changes'],
            'Follow-up Actions' => ['data' => 'followup_actions', 'name' => 'followup_actions', 'title' => 'Follow-up Actions'],
            'Note Actions' => ['data' => 'note_actions', 'name' => 'note_actions', 'title' => 'Note Actions'],
            'View' => ['data' => 'view', 'name' => 'view', 'title' => 'View', 'orderable' => false, 'searchable' => false],
        ];
    }
}
