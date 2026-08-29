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
            ->editColumn('total_leads_added', fn ($row) => (int) $row->total_leads_added)
            ->editColumn('converted_leads', fn ($row) => (int) $row->converted_leads)
            ->editColumn('lost_leads', fn ($row) => (int) $row->lost_leads)
            ->editColumn('active_leads', fn ($row) => (int) $row->active_leads)
            ->editColumn('conversion_percentage', fn ($row) => number_format((float) $row->conversion_percentage, 2))
            ->addIndexColumn()
            ->with('summary', $this->reportService->employeeLeadSummary($this->request()))
            ->rawColumns([]);
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
            'Total Leads Added' => ['data' => 'total_leads_added', 'name' => 'total_leads_added', 'title' => 'Total Leads Added'],
            'Converted Leads' => ['data' => 'converted_leads', 'name' => 'converted_leads', 'title' => 'Converted Leads'],
            'Lost Leads' => ['data' => 'lost_leads', 'name' => 'lost_leads', 'title' => 'Lost Leads'],
            'Active Leads' => ['data' => 'active_leads', 'name' => 'active_leads', 'title' => 'Active Leads'],
            'Conversion %' => ['data' => 'conversion_percentage', 'name' => 'conversion_percentage', 'title' => 'Conversion %'],
        ];
    }
}
