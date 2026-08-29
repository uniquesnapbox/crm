<?php

namespace App\DataTables;

use App\Services\LeadPerformanceReportService;
use Yajra\DataTables\Html\Button;

class LeadConversionReportDataTable extends BaseDataTable
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
            ->editColumn('converted_leads', fn ($row) => (int) $row->converted_leads)
            ->editColumn('revenue', fn ($row) => currency_format((float) $row->revenue, company()->currency_id))
            ->addIndexColumn()
            ->with('summary', $this->reportService->conversionSummary($this->request()))
            ->rawColumns(['revenue']);
    }

    public function query()
    {
        return $this->reportService->conversionDataQuery($this->request());
    }

    public function html()
    {
        $dataTable = $this->setBuilder('lead-conversion-report-table', 1)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["lead-conversion-report-table"].buttons().container().appendTo("#table-actions")
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
            'Converted Leads' => ['data' => 'converted_leads', 'name' => 'converted_leads', 'title' => 'Converted Leads'],
            'Revenue' => ['data' => 'revenue', 'name' => 'revenue', 'title' => 'Revenue'],
        ];
    }
}
