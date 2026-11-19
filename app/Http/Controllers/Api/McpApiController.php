<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\User;
use App\Services\CrmAuditService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class McpApiController extends Controller
{
    public function __construct(private readonly CrmAuditService $audit)
    {
    }

    public function leads(Request $request): JsonResponse
    {
        $this->permission('view_lead');
        $query = Lead::query()->with(['leadStatus:id,type', 'assignedTo:id,name']);
        $this->scopeLeads($query, $request->user());

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%' . addcslashes($search, '%_') . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('client_name', 'like', $like)
                    ->orWhere('client_email', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('company_name', 'like', $like);
            });
        }

        if ($statusId = $request->query('status_id')) {
            $query->where('status_id', (int) $statusId);
        }

        return response()->json($query->latest('id')->paginate($this->perPage($request)));
    }

    public function storeLead(Request $request): JsonResponse
    {
        $this->permission('add_lead', ['all', 'added', 'both']);
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:191'],
            'mobile' => ['nullable', 'string', 'max:191'],
            'client_email' => ['nullable', 'email:rfc,strict', 'max:191'],
            'company_name' => ['nullable', 'string', 'max:191'],
            'website' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'district' => ['nullable', 'string', 'max:191'],
            'area' => ['nullable', 'string', 'max:191'],
            'status_id' => ['nullable', 'integer', 'exists:lead_status,id'],
            'interest_level' => ['nullable', Rule::in(['low', 'medium', 'high', 'very_high'])],
            'deal_size' => ['nullable', 'numeric', 'min:0'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $this->ensureLeadAssignment($request, $data['assigned_to'] ?? null);
        $duplicates = $this->duplicateLeads($request->user()->company_id, $data);
        if ($duplicates->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Possible duplicate lead found. Confirm before creating another lead.',
                'duplicates' => $duplicates->map(fn (Lead $lead) => $this->leadData($lead))->values(),
            ], 409);
        }

        $lead = new Lead();
        $lead->forceFill(array_merge($data, [
            'company_id' => $request->user()->company_id,
            'added_by' => $request->user()->id,
            'last_updated_by' => $request->user()->id,
            'column_priority' => 0,
        ]));
        $lead->save();
        $this->audit->record($request, 'created', 'lead', $lead->id, null, $this->leadData($lead));

        return response()->json(['success' => true, 'data' => $this->leadData($lead->fresh())], 201);
    }

    public function updateLead(Request $request, Lead $lead): JsonResponse
    {
        $this->permission('edit_lead');
        $this->ensureLeadAccess($lead, $request->user());
        $data = $request->validate([
            'client_name' => ['sometimes', 'required', 'string', 'max:191'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:191'],
            'client_email' => ['sometimes', 'nullable', 'email:rfc,strict', 'max:191'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'website' => ['sometimes', 'nullable', 'string', 'max:191'],
            'country' => ['sometimes', 'nullable', 'string', 'max:191'],
            'state' => ['sometimes', 'nullable', 'string', 'max:191'],
            'district' => ['sometimes', 'nullable', 'string', 'max:191'],
            'area' => ['sometimes', 'nullable', 'string', 'max:191'],
            'status_id' => ['sometimes', 'nullable', 'integer', 'exists:lead_status,id'],
            'interest_level' => ['sometimes', 'nullable', Rule::in(['low', 'medium', 'high', 'very_high'])],
            'deal_size' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'assigned_to' => ['sometimes', 'nullable', 'integer'],
        ]);

        if (array_key_exists('assigned_to', $data)) {
            $this->ensureLeadAssignment($request, $data['assigned_to']);
        }
        if ($this->duplicateLeads($request->user()->company_id, $data, $lead->id)->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Another lead has the same mobile or email.'], 409);
        }

        $before = $this->leadData($lead);
        $lead->forceFill(array_merge($data, ['last_updated_by' => $request->user()->id]))->save();
        $after = $this->leadData($lead->fresh());
        $this->audit->record($request, 'updated', 'lead', $lead->id, $before, $after);

        return response()->json(['success' => true, 'data' => $after]);
    }

    public function deleteLead(Request $request, Lead $lead): JsonResponse
    {
        $this->permission('delete_lead', ['all', 'added', 'both', 'owned']);
        $this->ensureLeadAccess($lead, $request->user());
        abort_unless($request->boolean('confirm'), 422, 'Deletion requires confirm=true.');
        $before = $this->leadData($lead);
        $lead->delete();
        $this->audit->record($request, 'deleted', 'lead', $lead->id, $before, ['deleted_at' => now()->toIso8601String()]);

        return response()->json(['success' => true, 'message' => 'Lead soft-deleted.']);
    }

    public function updateLeadStatus(Request $request, Lead $lead): JsonResponse
    {
        $this->permission('edit_lead');
        $this->ensureLeadAccess($lead, $request->user());
        $data = $request->validate(['status_id' => ['required', 'integer', 'exists:lead_status,id']]);
        $before = $this->leadData($lead);
        $lead->forceFill(['status_id' => $data['status_id'], 'last_updated_by' => $request->user()->id])->save();
        $after = $this->leadData($lead->fresh());
        $this->audit->record($request, 'status_changed', 'lead', $lead->id, $before, $after);

        return response()->json(['success' => true, 'data' => $after]);
    }

    public function followUps(Request $request, Lead $lead): JsonResponse
    {
        $this->permission('view_lead_follow_up');
        $this->ensureLeadAccess($lead, $request->user());

        return response()->json(['data' => $lead->followUps()->latest('next_follow_up_date')->get()->map(fn (LeadFollowUp $f) => $this->followUpData($f))]);
    }

    public function pendingFollowUps(Request $request): JsonResponse
    {
        $this->permission('view_lead_follow_up');
        $query = LeadFollowUp::query()->with('lead:id,client_name,mobile,client_email,assigned_to,added_by')
            ->where('status', 'pending')->whereNotNull('next_follow_up_date');
        $query->whereHas('lead', fn (Builder $q) => $this->scopeLeads($q, $request->user()));
        if ($request->boolean('overdue')) {
            $query->where('next_follow_up_date', '<', now(company()->timezone));
        }

        return response()->json(['data' => $query->orderBy('next_follow_up_date')->limit(200)->get()->map(fn (LeadFollowUp $f) => $this->followUpData($f))]);
    }

    public function storeFollowUp(Request $request, Lead $lead): JsonResponse
    {
        $this->permission('add_lead_follow_up', ['all', 'added', 'both']);
        $this->ensureLeadAccess($lead, $request->user());
        $data = $request->validate([
            'note' => ['required', 'string', 'max:10000'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
        $followUp = new LeadFollowUp();
        $followUp->forceFill([
            'lead_id' => $lead->id,
            'remark' => trim($data['note']),
            'next_follow_up_date' => isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at'], company()->timezone)->utc() : null,
            'status' => 'pending',
            'send_reminder' => 'no',
            'added_by' => $request->user()->id,
            'last_updated_by' => $request->user()->id,
        ])->save();
        $this->audit->record($request, 'created', 'follow_up', $followUp->id, null, $this->followUpData($followUp));

        return response()->json(['success' => true, 'data' => $this->followUpData($followUp->fresh())], 201);
    }

    public function updateFollowUp(Request $request, Lead $lead, LeadFollowUp $followUp): JsonResponse
    {
        $this->permission('edit_lead_follow_up');
        $this->ensureLeadAccess($lead, $request->user());
        abort_unless((int) $followUp->lead_id === (int) $lead->id, 404);
        $data = $request->validate([
            'note' => ['sometimes', 'required', 'string', 'max:10000'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['pending', 'completed', 'canceled'])],
        ]);
        $before = $this->followUpData($followUp);
        if (array_key_exists('note', $data)) $followUp->remark = trim($data['note']);
        if (array_key_exists('scheduled_at', $data)) $followUp->next_follow_up_date = $data['scheduled_at'] ? Carbon::parse($data['scheduled_at'], company()->timezone)->utc() : null;
        if (array_key_exists('status', $data)) $followUp->status = $data['status'];
        $followUp->last_updated_by = $request->user()->id;
        $followUp->save();
        $after = $this->followUpData($followUp->fresh());
        $this->audit->record($request, 'updated', 'follow_up', $followUp->id, $before, $after);

        return response()->json(['success' => true, 'data' => $after]);
    }

    public function tasks(Request $request): JsonResponse
    {
        $this->permission('view_tasks');
        $query = $this->visibleTasks($request->user());
        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('heading', 'like', '%' . addcslashes($search, '%_') . '%');
        }
        if ($request->boolean('overdue')) {
            $query->whereNotNull('due_date')->where('due_date', '<', now(company()->timezone)->toDateString())->where('status', '!=', 'completed');
        }

        return response()->json($query->with(['users:id,name', 'project:id,project_name'])->latest('id')->paginate($this->perPage($request)));
    }

    public function storeTask(Request $request): JsonResponse
    {
        $this->permission('add_tasks', ['all', 'added', 'both']);
        $data = $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'project_id' => ['nullable', 'integer'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
        ]);
        $this->ensureProject($data['project_id'] ?? null, $request->user()->company_id);
        $userIds = $this->validEmployeeIds($data['user_ids'] ?? [], $request->user()->company_id);
        abort_if(empty($userIds) && $request->user()->permission('create_unassigned_tasks') !== 'all', 422, 'At least one assignee is required.');
        $column = TaskboardColumn::where('slug', 'incomplete')->first() ?: TaskboardColumn::query()->orderBy('priority')->firstOrFail();
        $task = new Task();
        $task->forceFill([
            'company_id' => $request->user()->company_id,
            'heading' => $data['heading'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'start_date' => isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            'due_date' => isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            'project_id' => $data['project_id'] ?? null,
            'status' => 'incomplete',
            'board_column_id' => $column->id,
            'column_priority' => (int) Task::where('board_column_id', $column->id)->max('column_priority') + 1,
            'created_by' => $request->user()->id,
            'added_by' => $request->user()->id,
            'last_updated_by' => $request->user()->id,
            'is_private' => false,
            'billable' => true,
            'estimate_hours' => 0,
            'estimate_minutes' => 0,
        ])->save();
        $task->users()->sync($userIds);
        $this->audit->record($request, 'created', 'task', $task->id, null, $this->taskData($task->fresh(['users', 'project'])));

        return response()->json(['success' => true, 'data' => $this->taskData($task->fresh(['users', 'project']))], 201);
    }

    public function updateTask(Request $request, Task $task): JsonResponse
    {
        $this->ensureTaskAccess($task, $request->user(), 'edit_tasks');
        $data = $request->validate([
            'heading' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ]);
        $before = $this->taskData($task);
        foreach (['heading', 'description', 'priority', 'start_date', 'due_date'] as $field) {
            if (array_key_exists($field, $data)) $task->{$field} = in_array($field, ['start_date', 'due_date'], true) && $data[$field] ? Carbon::parse($data[$field]) : $data[$field];
        }
        $task->last_updated_by = $request->user()->id;
        $task->save();
        $after = $this->taskData($task->fresh(['users', 'project']));
        $this->audit->record($request, 'updated', 'task', $task->id, $before, $after);

        return response()->json(['success' => true, 'data' => $after]);
    }

    public function updateTaskStatus(Request $request, Task $task): JsonResponse
    {
        $this->ensureTaskAccess($task, $request->user(), 'change_status');
        $data = $request->validate(['status' => ['required', 'string', 'max:80']]);
        $column = TaskboardColumn::where('slug', $data['status'])->firstOrFail();
        $before = $this->taskData($task);
        $task->board_column_id = $column->id;
        $task->status = $column->slug === 'completed' ? 'completed' : 'incomplete';
        $task->completed_on = $task->status === 'completed' ? now(company()->timezone) : null;
        $task->last_updated_by = $request->user()->id;
        $task->save();
        $after = $this->taskData($task->fresh(['users', 'project']));
        $this->audit->record($request, 'status_changed', 'task', $task->id, $before, $after);

        return response()->json(['success' => true, 'data' => $after]);
    }

    public function assignTask(Request $request, Task $task): JsonResponse
    {
        $this->ensureTaskAccess($task, $request->user(), 'edit_tasks');
        $data = $request->validate(['user_ids' => ['nullable', 'array'], 'user_ids.*' => ['integer']]);
        $userIds = $this->validEmployeeIds($data['user_ids'] ?? [], $request->user()->company_id);
        $before = $this->taskData($task->fresh('users'));
        $task->users()->sync($userIds);
        $task->last_updated_by = $request->user()->id;
        $task->save();
        $after = $this->taskData($task->fresh(['users', 'project']));
        $this->audit->record($request, 'assigned', 'task', $task->id, $before, $after);

        return response()->json(['success' => true, 'data' => $after]);
    }

    public function deleteTask(Request $request, Task $task): JsonResponse
    {
        $this->ensureTaskAccess($task, $request->user(), 'delete_tasks');
        abort_unless($request->boolean('confirm'), 422, 'Deletion requires confirm=true.');
        $before = $this->taskData($task);
        $task->delete();
        $this->audit->record($request, 'deleted', 'task', $task->id, $before, ['deleted_at' => now()->toIso8601String()]);

        return response()->json(['success' => true, 'message' => 'Task soft-deleted.']);
    }

    public function performanceReport(Request $request): JsonResponse
    {
        $this->permission('view_lead');
        $period = $request->query('period', 'monthly');
        abort_unless(in_array($period, ['daily', 'weekly', 'monthly', 'custom'], true), 422, 'Invalid report period.');
        $tz = company()->timezone;
        $from = $request->filled('from') ? Carbon::parse($request->query('from'), $tz)->startOfDay() : match ($period) {
            'daily' => now($tz)->startOfDay(),
            'weekly' => now($tz)->startOfWeek(),
            default => now($tz)->startOfMonth(),
        };
        $to = $request->filled('to') ? Carbon::parse($request->query('to'), $tz)->endOfDay() : now($tz)->endOfDay();
        abort_if($to->lt($from) || $from->diffInDays($to) > 366, 422, 'Report range must be valid and no longer than one year.');

        $companyId = $request->user()->company_id;
        $leadQuery = Lead::query()->where('company_id', $companyId)->whereBetween('created_at', [$from->utc(), $to->utc()]);
        $taskQuery = Task::query()->where('company_id', $companyId)->whereBetween('created_at', [$from->utc(), $to->utc()]);
        $canViewTasks = in_array($request->user()->permission('view_tasks'), ['all', 'added', 'owned', 'both'], true);
        $canViewFollowUps = in_array($request->user()->permission('view_lead_follow_up'), ['all', 'added', 'owned', 'both'], true);
        $data = [
            'period' => $period,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'leads_created' => (clone $leadQuery)->count(),
            'leads_converted' => (clone $leadQuery)->whereNotNull('client_id')->count(),
            'tasks_created' => $canViewTasks ? (clone $taskQuery)->count() : null,
            'tasks_completed' => $canViewTasks ? (clone $taskQuery)->where('status', 'completed')->count() : null,
            'follow_ups_created' => $canViewFollowUps ? LeadFollowUp::whereHas('lead', fn (Builder $q) => $q->where('company_id', $companyId))->whereBetween('created_at', [$from->utc(), $to->utc()])->count() : null,
            'follow_ups_completed' => $canViewFollowUps ? LeadFollowUp::whereHas('lead', fn (Builder $q) => $q->where('company_id', $companyId))->where('status', 'completed')->whereBetween('updated_at', [$from->utc(), $to->utc()])->count() : null,
        ];

        return response()->json(['data' => $data]);
    }

    private function permission(string $name, array $allowed = ['all', 'added', 'owned', 'both']): void
    {
        abort_403(!in_array(user()->permission($name), $allowed, true));
    }

    private function scopeLeads(Builder $query, User $user): void
    {
        $query->where('company_id', $user->company_id);
        $permission = $user->permission('view_lead');
        if ($permission === 'all' || in_array('admin', user_roles(), true)) return;
        if ($permission === 'added') $query->where('added_by', $user->id);
        elseif ($permission === 'owned') $query->where('assigned_to', $user->id);
        elseif ($permission === 'both') $query->where(fn (Builder $q) => $q->where('added_by', $user->id)->orWhere('assigned_to', $user->id));
        else $query->whereRaw('1 = 0');
    }

    private function ensureLeadAccess(Lead $lead, User $user): void
    {
        abort_unless((int) $lead->company_id === (int) $user->company_id, 404);
        $query = Lead::query()->whereKey($lead->id);
        $this->scopeLeads($query, $user);
        abort_unless($query->exists(), 403);
    }

    private function ensureLeadAssignment(Request $request, mixed $userId): void
    {
        if ($userId === null) return;
        abort_unless($request->user()->permission('add_lead') === 'all', 403, 'Only users with all lead permission can assign leads.');
        abort_unless(User::whereKey($userId)->where('company_id', $request->user()->company_id)->whereHas('roles', fn ($q) => $q->where('name', 'employee'))->exists(), 422, 'Invalid lead assignee.');
    }

    private function duplicateLeads(int $companyId, array $data, ?int $exceptId = null)
    {
        if (empty($data['mobile']) && empty($data['client_email'])) {
            return collect();
        }

        return Lead::query()->where('company_id', $companyId)->when($exceptId, fn ($q) => $q->where('id', '<>', $exceptId))
            ->where(function (Builder $q) use ($data) {
                if (!empty($data['mobile'])) $q->orWhere('mobile', $data['mobile']);
                if (!empty($data['client_email'])) $q->orWhereRaw('LOWER(client_email) = ?', [strtolower($data['client_email'])]);
            })->limit(5)->get();
    }

    private function visibleTasks(User $user): Builder
    {
        $query = Task::query()->where('company_id', $user->company_id);
        $permission = $user->permission('view_tasks');
        if ($permission === 'all' || in_array('admin', user_roles(), true)) return $query;
        if ($permission === 'added') return $query->where('added_by', $user->id);
        if ($permission === 'owned') return $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        if ($permission === 'both') return $query->where(fn ($q) => $q->where('added_by', $user->id)->orWhereHas('users', fn ($u) => $u->where('users.id', $user->id)));
        return $query->whereRaw('1 = 0');
    }

    private function ensureTaskAccess(Task $task, User $user, string $permission): void
    {
        abort_unless((int) $task->company_id === (int) $user->company_id, 404);
        $value = $user->permission($permission);
        $allowed = $value === 'all' || (in_array($value, ['added', 'both'], true) && (int) $task->added_by === (int) $user->id) || (in_array($value, ['owned', 'both'], true) && $task->users()->whereKey($user->id)->exists());
        abort_unless($allowed || in_array('admin', user_roles(), true), 403);
    }

    private function ensureProject(?int $projectId, int $companyId): void
    {
        if ($projectId === null) return;
        abort_unless(DB::table('projects')->where('id', $projectId)->where('company_id', $companyId)->whereNull('deleted_at')->exists(), 422, 'Invalid project.');
    }

    private function validEmployeeIds(array $ids, int $companyId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) return [];
        return User::query()->whereIn('id', $ids)->where('company_id', $companyId)->where('status', 'active')->whereHas('roles', fn ($q) => $q->where('name', 'employee'))->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function leadData(Lead $lead): array
    {
        return ['id' => $lead->id, 'name' => $lead->client_name, 'email' => $lead->client_email, 'mobile' => $lead->mobile, 'company_name' => $lead->company_name, 'status_id' => $lead->status_id, 'status' => $lead->leadStatus?->type, 'assigned_to' => $lead->assignedTo?->name, 'created_at' => $lead->created_at?->toIso8601String()];
    }

    private function followUpData(LeadFollowUp $followUp): array
    {
        return ['id' => $followUp->id, 'lead_id' => $followUp->lead_id, 'lead_name' => $followUp->lead?->client_name, 'note' => $followUp->remark, 'scheduled_at' => $followUp->next_follow_up_date?->toIso8601String(), 'status' => $followUp->status ?: 'pending', 'created_at' => $followUp->created_at?->toIso8601String()];
    }

    private function taskData(Task $task): array
    {
        return ['id' => $task->id, 'heading' => $task->heading, 'description' => $task->description, 'priority' => $task->priority, 'status' => $task->status, 'due_date' => $task->due_date?->toIso8601String(), 'start_date' => $task->start_date?->toIso8601String(), 'project' => $task->project?->project_name, 'assignees' => $task->users?->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()];
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 20), 1), 50);
    }
}
