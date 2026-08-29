<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeadPerformanceReportService
{
    public function employees()
    {
        return User::allEmployees(null, true, null, company()->id);
    }

    public function leadSources()
    {
        return LeadSource::query()
            ->select('id', 'type')
            ->orderBy('type')
            ->get();
    }

    public function leadStatuses()
    {
        return LeadStatus::query()
            ->select('id', 'type', 'label_color', 'priority', 'default')
            ->orderBy('priority')
            ->get();
    }

    public function employeeLeadDataQuery($request): Builder
    {
        return $this->employeeBaseQuery($request)
            ->select([
                'users.id as employee_id',
                'users.name as employee_name',
                DB::raw('COUNT(leads.id) as total_leads_added'),
                DB::raw('SUM(CASE WHEN leads.client_id IS NOT NULL THEN 1 ELSE 0 END) as converted_leads'),
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(lead_status.type, '')) = 'lost' THEN 1 ELSE 0 END) as lost_leads"),
                DB::raw("SUM(CASE WHEN leads.client_id IS NULL AND LOWER(COALESCE(lead_status.type, '')) <> 'lost' THEN 1 ELSE 0 END) as active_leads"),
                DB::raw('ROUND((SUM(CASE WHEN leads.client_id IS NOT NULL THEN 1 ELSE 0 END) * 100) / NULLIF(COUNT(leads.id), 0), 2) as conversion_percentage'),
            ])
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name');
    }

    public function employeeLeadSummary($request): array
    {
        $summary = $this->employeeBaseQuery($request)
            ->selectRaw('COUNT(leads.id) as total_leads_added')
            ->selectRaw('SUM(CASE WHEN leads.client_id IS NOT NULL THEN 1 ELSE 0 END) as converted_leads')
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(lead_status.type, '')) = 'lost' THEN 1 ELSE 0 END) as lost_leads")
            ->selectRaw("SUM(CASE WHEN leads.client_id IS NULL AND LOWER(COALESCE(lead_status.type, '')) <> 'lost' THEN 1 ELSE 0 END) as active_leads")
            ->selectRaw('ROUND((SUM(CASE WHEN leads.client_id IS NOT NULL THEN 1 ELSE 0 END) * 100) / NULLIF(COUNT(leads.id), 0), 2) as conversion_percentage')
            ->first();

        return [
            'total_leads_added' => (int) ($summary->total_leads_added ?? 0),
            'converted_leads' => (int) ($summary->converted_leads ?? 0),
            'lost_leads' => (int) ($summary->lost_leads ?? 0),
            'active_leads' => (int) ($summary->active_leads ?? 0),
            'conversion_percentage' => (float) ($summary->conversion_percentage ?? 0),
        ];
    }

    public function conversionDataQuery($request): Builder
    {
        return $this->conversionBaseQuery($request)
            ->select([
                'users.id as employee_id',
                'users.name as employee_name',
                DB::raw('COUNT(leads.id) as converted_leads'),
                DB::raw('COALESCE(SUM(COALESCE(client_details.lead_deal_size, leads.deal_size, 0)), 0) as revenue'),
            ])
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name');
    }

    public function conversionSummary($request): array
    {
        $summary = $this->conversionBaseQuery($request)
            ->selectRaw('COUNT(leads.id) as converted_leads')
            ->selectRaw('COALESCE(SUM(COALESCE(client_details.lead_deal_size, leads.deal_size, 0)), 0) as revenue')
            ->first();

        return [
            'converted_leads' => (int) ($summary->converted_leads ?? 0),
            'revenue' => (float) ($summary->revenue ?? 0),
        ];
    }

    private function employeeBaseQuery($request): Builder
    {
        $query = Lead::query()
            ->join('users', 'users.id', '=', 'leads.added_by')
            ->leftJoin('lead_status', 'lead_status.id', '=', 'leads.status_id');

        $this->applyVisibilityScope($query);
        $this->applySharedFilters($query, $request, 'leads.created_at', 'startDate', 'endDate');

        return $query;
    }

    private function conversionBaseQuery($request): Builder
    {
        $query = Lead::query()
            ->join('users', 'users.id', '=', 'leads.added_by')
            ->leftJoin('users as client_user', 'client_user.id', '=', 'leads.client_id')
            ->leftJoin('client_details', function ($join) {
                $join->on('client_details.user_id', '=', 'client_user.id')
                    ->where('client_details.company_id', '=', company()->id);
            })
            ->whereNotNull('leads.client_id');

        $this->applyVisibilityScope($query);
        $this->applySharedFilters($query, $request, 'COALESCE(leads.converted_at, client_user.created_at)', 'fromDate', 'toDate');

        return $query;
    }

    private function applyVisibilityScope(Builder $query): void
    {
        $permission = user()->permission('view_lead');

        if ($permission === 'all') {
            return;
        }

        if ($permission === 'added') {
            $query->where('leads.added_by', user()->id);

            return;
        }

        if ($permission === 'owned') {
            $query->where('leads.assigned_to', user()->id);

            return;
        }

        $query->where(function ($builder) {
            $builder->where('leads.added_by', user()->id)
                ->orWhere('leads.assigned_to', user()->id);
        });
    }

    private function applySharedFilters(Builder $query, $request, string $dateExpression, string $startKey, string $endKey): void
    {
        if ($request->filled('employee') && $request->employee !== 'all') {
            $query->where('leads.added_by', (int) $request->employee);
        }

        if ($request->filled('source_id') && $request->source_id !== 'all') {
            $query->where('leads.source_id', (int) $request->source_id);
        }

        if ($request->filled('status_id') && $request->status_id !== 'all') {
            $query->where('leads.status_id', (int) $request->status_id);
        }

        $startValue = $request->input($startKey);
        if ($startValue !== null && $startValue !== '' && $startValue !== 'null') {
            $startDate = companyToDateString($startValue);
            $startBoundary = Carbon::parse($startDate, company()->timezone)->startOfDay()->toDateTimeString();
            $query->whereRaw("{$dateExpression} >= ?", [$startBoundary]);
        }

        $endValue = $request->input($endKey);
        if ($endValue !== null && $endValue !== '' && $endValue !== 'null') {
            $endDate = companyToDateString($endValue);
            $endBoundary = Carbon::parse($endDate, company()->timezone)->endOfDay()->toDateTimeString();
            $query->whereRaw("{$dateExpression} <= ?", [$endBoundary]);
        }
    }
}
