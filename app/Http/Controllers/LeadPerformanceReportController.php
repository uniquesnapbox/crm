<?php

namespace App\Http\Controllers;

use App\DataTables\EmployeeLeadReportDataTable;
use App\DataTables\LeadConversionReportDataTable;
use App\Services\LeadPerformanceReportService;
use Illuminate\Http\Request;

class LeadPerformanceReportController extends AccountBaseController
{
    public function __construct(private readonly LeadPerformanceReportService $reportService)
    {
        parent::__construct();
        $this->pageTitle = 'Lead Reports';
    }

    public function employee(EmployeeLeadReportDataTable $dataTable)
    {
        abort_403(user()->permission('view_lead_report') === 'none');

        if (!request()->ajax()) {
            $this->pageTitle = 'Employee Lead Report';
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone)->endOfDay();
            $this->employees = $this->reportService->employees();
            $this->sources = $this->reportService->leadSources();
            $this->statuses = $this->reportService->leadStatuses();
            $this->summary = $this->reportService->employeeLeadSummary($this->reportRequestWithDefaults('startDate', 'endDate'));
        }

        return $dataTable->render('reports.lead-performance.employee', $this->data);
    }

    public function conversion(LeadConversionReportDataTable $dataTable)
    {
        abort_403(user()->permission('view_lead_report') === 'none');

        if (!request()->ajax()) {
            $this->pageTitle = 'Lead Conversion Report';
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone)->endOfDay();
            $this->employees = $this->reportService->employees();
            $this->sources = $this->reportService->leadSources();
            $this->statuses = $this->reportService->leadStatuses();
            $this->summary = $this->reportService->conversionSummary($this->reportRequestWithDefaults('fromDate', 'toDate'));
        }

        return $dataTable->render('reports.lead-performance.conversion', $this->data);
    }

    private function reportRequestWithDefaults(string $startKey, string $endKey): Request
    {
        $request = request()->duplicate();

        if (!$request->filled($startKey) || !$request->filled($endKey)) {
            $request->merge([
                $startKey => now($this->company->timezone)->startOfMonth()->translatedFormat($this->company->date_format),
                $endKey => now($this->company->timezone)->endOfDay()->translatedFormat($this->company->date_format),
            ]);
        }

        return $request;
    }
}
