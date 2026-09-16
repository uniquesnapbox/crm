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
    private const LEAD_ACTIVITY_FIELDS = [
        'source_id',
        'category_id',
        'status_id',
        'interest_level',
        'contact_status',
    ];

    private const LEAD_ACTIVITY_CHANGE_LABELS = [
        'status_id' => 'Status',
        'interest_level' => 'Interest',
        'contact_status' => 'Contact Status',
        'category_id' => 'Category',
        'source_id' => 'Source',
    ];

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
        [$start, $end] = $this->activityUtcRange($request);

        $query = User::query()
            ->withRole('employee')
            ->join('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->where('users.company_id', company()->id)
            ->select([
                'users.id as employee_id',
                'users.name as employee_name',
            ])
            ->selectSub($this->employeeLeadsContactedQuery($request, $start, $end), 'leads_contacted')
            ->selectSub($this->employeeStatusChangedQuery($request, $start, $end), 'status_changed')
            ->selectSub($this->employeeFollowupsQuery($request, $start, $end), 'followups')
            ->orderBy('users.name');

        if ($request->filled('employee') && $request->employee !== 'all') {
            $query->where('users.id', (int) $request->employee);
        }

        return $query;
    }

    public function employeeActivityDetails(int $employeeId, $request): array
    {
        [$start, $end] = $this->activityUtcRange($request);
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));
        $page = max(1, (int) $request->input('page', 1));

        $historyLeads = DB::table('lead_histories as history')
            ->where('history.company_id', company()->id)
            ->where('history.created_by', $employeeId)
            ->whereBetween('history.event_at', [$start, $end])
            ->where(function ($eventQuery) {
                $eventQuery->where('history.event_type', 'lead_field_updated')
                    ->orWhereIn('history.event_type', [
                        'followup_created',
                        'followup_updated',
                        'followup_status_updated',
                        'followup_deleted',
                    ]);
            })
            ->select([
                'history.lead_id',
                DB::raw('history.event_at as activity_at'),
            ]);
        $this->applyActivityLeadFilters($historyLeads, $request, 'history.lead_id');

        $followupLeads = DB::table('lead_follow_up as followup')
            ->where('followup.added_by', $employeeId)
            ->whereBetween('followup.created_at', [$start, $end])
            ->select([
                'followup.lead_id',
                DB::raw('followup.created_at as activity_at'),
            ]);
        $this->applyActivityLeadFilters($followupLeads, $request, 'followup.lead_id');

        $activityLeadPage = DB::query()
            ->fromSub($historyLeads->unionAll($followupLeads), 'employee_activity_leads')
            ->select('lead_id', DB::raw('MAX(activity_at) as last_activity_at'))
            ->groupBy('lead_id')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('lead_id')
            ->paginate($perPage, ['lead_id', 'last_activity_at'], 'page', $page);

        $leadIds = collect($activityLeadPage->items())->pluck('lead_id')->map(fn ($id) => (int) $id)->values();
        if ($leadIds->isEmpty()) {
            return [
                'rows' => collect(),
                'has_more' => false,
                'next_page' => null,
            ];
        }

        $leads = DB::table('leads')
            ->leftJoin('lead_category', 'lead_category.id', '=', 'leads.category_id')
            ->leftJoin('lead_status', 'lead_status.id', '=', 'leads.status_id')
            ->where('leads.company_id', company()->id)
            ->whereIn('leads.id', $leadIds->all())
            ->select([
                'leads.id',
                'leads.client_name',
                'leads.mobile',
                'leads.cell',
                'leads.office',
                'leads.interest_level',
                'lead_category.category_name as lead_category',
                'lead_status.type as lead_status',
            ])
            ->get()
            ->keyBy('id');

        $followups = DB::table('lead_follow_up as followup')
            ->whereIn('followup.lead_id', $leadIds->all())
            ->where('followup.added_by', $employeeId)
            ->select([
                'followup.id',
                'followup.lead_id',
                'followup.status',
                'followup.next_follow_up_date',
                'followup.created_at',
            ])
            ->orderByDesc('followup.next_follow_up_date')
            ->orderByDesc('followup.id')
            ->get()
            ->groupBy('lead_id');

        $histories = DB::table('lead_histories as history')
            ->where('history.company_id', company()->id)
            ->where('history.created_by', $employeeId)
            ->whereIn('history.lead_id', $leadIds->all())
            ->where('history.event_type', 'lead_field_updated')
            ->whereIn('history.field_key', array_keys(self::LEAD_ACTIVITY_CHANGE_LABELS))
            ->whereBetween('history.event_at', [$start, $end])
            ->select([
                'history.id',
                'history.lead_id',
                'history.field_key',
                'history.old_value',
                'history.new_value',
                'history.event_at',
            ])
            ->orderBy('history.event_at')
            ->orderBy('history.id')
            ->get()
            ->groupBy('lead_id');

        $rows = $leadIds->map(function (int $leadId) use ($leads, $followups, $histories) {
            $lead = $leads->get($leadId);
            if (!$lead) {
                return null;
            }

            $leadFollowups = $followups->get($leadId, collect());
            $nextFollowup = $leadFollowups
                ->filter(fn ($followup) => filled($followup->next_follow_up_date) && ($followup->status ?: 'pending') === 'pending')
                ->sortBy('next_follow_up_date')
                ->first();

            $currentFollowupDate = $nextFollowup?->next_follow_up_date
                ? Carbon::parse($nextFollowup->next_follow_up_date)
                : null;
            $previousFollowup = $leadFollowups
                ->filter(function ($followup) use ($nextFollowup, $currentFollowupDate) {
                    if (!filled($followup->next_follow_up_date)
                        || ($nextFollowup && (int) $followup->id === (int) $nextFollowup->id) ) {
                        return false;
                    }

                    return !$currentFollowupDate
                        || Carbon::parse($followup->next_follow_up_date)->lt($currentFollowupDate);
                })
                ->sortByDesc('next_follow_up_date')
                ->first();

            $latestChanges = $histories->get($leadId, collect())
                ->groupBy('field_key')
                ->map(fn ($fieldHistories) => $fieldHistories->last());
            $previousValue = static function ($value) {
                return filled($value) && $value !== '--' ? $value : null;
            };
            $currentInterest = $lead->interest_level
                ? ucwords(str_replace('_', ' ', (string) $lead->interest_level))
                : null;

            return [
                'name' => $lead->client_name ?: 'Lead #' . $lead->id,
                'number' => $lead->mobile ?: ($lead->cell ?: $lead->office),
                'category' => $lead->lead_category,
                'status' => [
                    'current' => $lead->lead_status,
                    'previous' => $previousValue($latestChanges->get('status_id')?->old_value),
                ],
                'interest_level' => [
                    'current' => $currentInterest,
                    'previous' => $previousValue($latestChanges->get('interest_level')?->old_value),
                ],
                'followup_date' => [
                    'current' => $nextFollowup?->next_follow_up_date,
                    'previous' => $previousFollowup?->next_follow_up_date,
                ],
            ];
        })->filter()->values();

        return [
            'rows' => $rows,
            'has_more' => $activityLeadPage->hasMorePages(),
            'next_page' => $activityLeadPage->hasMorePages() ? $activityLeadPage->currentPage() + 1 : null,
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

    private function employeeLeadsContactedQuery($request, Carbon $start, Carbon $end)
    {
        $query = DB::table('lead_follow_up')
            ->whereColumn('lead_follow_up.added_by', 'users.id')
            ->whereBetween('lead_follow_up.created_at', [$start, $end])
            ->selectRaw('COUNT(DISTINCT lead_follow_up.lead_id)');

        $this->applyActivityLeadFilters($query, $request, 'lead_follow_up.lead_id');

        return $query;
    }

    private function employeeStatusChangedQuery($request, Carbon $start, Carbon $end)
    {
        $query = DB::table('lead_histories')
            ->whereColumn('lead_histories.created_by', 'users.id')
            ->where('lead_histories.company_id', company()->id)
            ->where('lead_histories.event_type', 'lead_field_updated')
            ->whereIn('lead_histories.field_key', self::LEAD_ACTIVITY_FIELDS)
            ->whereBetween('lead_histories.event_at', [$start, $end])
            ->selectRaw('COUNT(DISTINCT lead_histories.lead_id)');

        $this->applyActivityLeadFilters($query, $request, 'lead_histories.lead_id');

        return $query;
    }

    private function employeeFollowupsQuery($request, Carbon $start, Carbon $end)
    {
        $query = DB::table('lead_follow_up')
            ->whereColumn('lead_follow_up.added_by', 'users.id')
            ->whereBetween('lead_follow_up.created_at', [$start, $end])
            ->selectRaw('COUNT(*)');

        $this->applyActivityLeadFilters($query, $request, 'lead_follow_up.lead_id');

        return $query;
    }

    private function applyActivityLeadFilters($query, $request, string $leadIdColumn): void
    {
        $query->whereExists(function ($leadQuery) use ($request, $leadIdColumn) {
            $leadQuery->selectRaw('1')
                ->from('leads as activity_leads')
                ->whereColumn('activity_leads.id', $leadIdColumn)
                ->where('activity_leads.company_id', company()->id);

            $permission = user()->permission('view_lead');

            if ($permission === 'added') {
                $leadQuery->where('activity_leads.added_by', user()->id);
            } elseif ($permission === 'owned') {
                $leadQuery->where('activity_leads.assigned_to', user()->id);
            } elseif ($permission !== 'all') {
                $leadQuery->where(function ($visibilityQuery) {
                    $visibilityQuery->where('activity_leads.added_by', user()->id)
                        ->orWhere('activity_leads.assigned_to', user()->id);
                });
            }

            if ($request->filled('source_id') && $request->source_id !== 'all') {
                $leadQuery->where('activity_leads.source_id', (int) $request->source_id);
            }

            if ($request->filled('status_id') && $request->status_id !== 'all') {
                $leadQuery->where('activity_leads.status_id', (int) $request->status_id);
            }
        });
    }

    private function todayUtcRange(): array
    {
        $today = Carbon::now(company()->timezone);

        return [
            $today->copy()->startOfDay()->utc(),
            $today->copy()->endOfDay()->utc(),
        ];
    }

    private function activityUtcRange($request): array
    {
        $startValue = $request->input('startDate');
        $endValue = $request->input('endDate');

        if ($startValue === null || $startValue === '' || $startValue === 'null'
            || $endValue === null || $endValue === '' || $endValue === 'null') {
            return $this->todayUtcRange();
        }

        $startDate = companyToDateString($startValue);
        $endDate = companyToDateString($endValue);

        return [
            Carbon::parse($startDate, company()->timezone)->startOfDay()->utc(),
            Carbon::parse($endDate, company()->timezone)->endOfDay()->utc(),
        ];
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
