<?php

namespace App\Http\Controllers;

use App\DataTables\EmployeeLeadReportDataTable;
use App\DataTables\LeadConversionReportDataTable;
use App\Models\User;
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
        }

        return $dataTable->render('reports.lead-performance.employee', $this->data);
    }

    public function statusChanges(int $employee)
    {
        abort_403(user()->permission('view_lead_report') === 'none');

        $employeeUser = User::query()
            ->withRole('employee')
            ->where('company_id', company()->id)
            ->findOrFail($employee);

        $activity = $this->reportService->employeeActivityDetails($employee, request());
        $view = request()->boolean('append')
            ? 'reports.lead-performance.status-changes-rows'
            : 'reports.lead-performance.status-changes';
        $viewData = [
            'employee' => $employeeUser,
            'rows' => $activity['rows'],
            'summary' => $activity['summary'],
            'hasMore' => $activity['has_more'],
            'nextPage' => $activity['next_page'],
        ];

        return response()->json([
            'title' => 'Lead Activity - ' . $employeeUser->name,
            'html' => view($view, $viewData)->render(),
            'has_more' => $activity['has_more'],
            'next_page' => $activity['next_page'],
        ]);
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
