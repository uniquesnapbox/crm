<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\ClientResource;
use App\Models\Country;
use App\Models\Lead;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MobileCrmApiController extends Controller
{
    public function clients(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $viewPermission = $user->permission('view_clients');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both'], true));

        $perPage = $this->perPage($request);
        $query = User::withoutGlobalScope(ActiveScope::class)
            ->with('clientDetails')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->leftJoin('client_details', 'users.id', '=', 'client_details.user_id')
            ->leftJoin('countries', 'countries.id', '=', 'users.country_id')
            ->leftJoin('users as added_by_user', 'added_by_user.id', '=', 'client_details.added_by')
            ->where('roles.name', 'client')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.mobile',
                'users.status',
                'users.created_at',
                'users.country_id',
                'client_details.company_name',
                'client_details.address',
                'client_details.shipping_address',
                'client_details.postal_code',
                'client_details.state',
                'client_details.city',
                'client_details.office',
                'client_details.cell',
                'client_details.website',
                'client_details.note',
                'client_details.client_type',
                'client_details.lead_source_id',
                'client_details.lead_category_id',
                'client_details.lead_status_id',
                'client_details.lead_interest_level',
                'client_details.lead_deal_size',
                'client_details.lead_contact_status',
                'client_details.lead_contact_status_reason',
                'client_details.products_services',
                'client_details.last_contact_date',
                'client_details.next_followup_date',
                'client_details.skype',
                'client_details.facebook',
                'client_details.twitter',
                'client_details.linkedin',
                'client_details.tax_name',
                'client_details.gst_number',
                'client_details.electronic_address',
                'client_details.electronic_address_scheme',
                'client_details.company_logo',
                'added_by_user.name as added_by',
                'countries.name as country',
            ]);

        if (in_array($viewPermission, ['added', 'both'], true)) {
            $query->where('client_details.added_by', $user->id);
        }

        if ($user->hasRole('client')) {
            $query->where('client_details.user_id', $user->id);
        }

        $status = trim((string) $request->get('status', ''));
        if ($status !== '' && $status !== 'all') {
            $query->where('users.status', $status);
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.mobile', 'like', "%{$search}%")
                    ->orWhere('client_details.company_name', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderBy('users.name')->paginate($perPage)
        );
    }

    public function client(Request $request, int $clientId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $viewPermission = $user->permission('view_clients');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both'], true));

        $query = User::withoutGlobalScope(ActiveScope::class)
            ->with([
                'clientDetails.addedBy:id,name',
                'convertedLead.addedBy:id,name',
                'country:id,name',
            ])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->leftJoin('client_details', 'users.id', '=', 'client_details.user_id')
            ->leftJoin('users as added_by_user', 'added_by_user.id', '=', 'client_details.added_by')
            ->leftJoin('countries', 'countries.id', '=', 'users.country_id')
            ->where('roles.name', 'client')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.mobile',
                'users.status',
                'users.image',
                'users.created_at',
                'users.country_id',
                'client_details.company_name',
                'client_details.address',
                'client_details.shipping_address',
                'client_details.postal_code',
                'client_details.state',
                'client_details.city',
                'client_details.office',
                'client_details.cell',
                'client_details.website',
                'client_details.note',
                'client_details.client_type',
                'client_details.lead_source_id',
                'client_details.lead_category_id',
                'client_details.lead_status_id',
                'client_details.lead_interest_level',
                'client_details.lead_deal_size',
                'client_details.lead_contact_status',
                'client_details.lead_contact_status_reason',
                'client_details.products_services',
                'client_details.last_contact_date',
                'client_details.next_followup_date',
                'client_details.skype',
                'client_details.facebook',
                'client_details.twitter',
                'client_details.linkedin',
                'client_details.tax_name',
                'client_details.gst_number',
                'client_details.electronic_address',
                'client_details.electronic_address_scheme',
                'client_details.company_logo',
                'added_by_user.name as added_by',
                'countries.name as country',
            ]);

        if (in_array($viewPermission, ['added', 'both'], true)) {
            $query->where('client_details.added_by', $user->id);
        }

        if ($user->hasRole('client')) {
            $query->where('client_details.user_id', $user->id);
        }

        $client = $query->where('users.id', $clientId)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        return (new ClientResource($client))
            ->additional([
                'success' => true,
                'message' => 'Client fetched successfully.',
            ])
            ->response();
    }

    public function updateClient(Request $request, int $clientId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $editPermission = $user->permission('edit_clients');
        abort_403(!in_array($editPermission, ['all', 'added', 'both'], true));

        $client = User::withoutGlobalScope(ActiveScope::class)
            ->with('clientDetails')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->leftJoin('client_details', 'users.id', '=', 'client_details.user_id')
            ->where('roles.name', 'client')
            ->select('users.*', 'client_details.added_by as client_added_by')
            ->where('users.id', $clientId)
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        $addedBy = (int) ($client->client_added_by ?? 0);
        abort_403(!(
            $editPermission === 'all'
            || ($editPermission === 'added' && $addedBy === $user->id)
            || ($editPermission === 'both' && $addedBy === $user->id)
        ));

        $client->name = $this->cleanInput($request->input('name')) ?? $client->name;
        $client->email = $this->cleanInput($request->input('email'));
        $client->mobile = $this->normalizePhone($request->input('mobile'));
        $client->status = $this->normalizeStatus($request->input('status', $client->status));
        $client->country_id = $request->has('country')
            ? $this->resolveCountryId($request->input('country'), null)
            : $client->country_id;
        $client->save();

        $client->clientDetails()->updateOrCreate(
            ['user_id' => $client->id],
            [
                'company_id' => company()->id,
                'company_name' => $this->cleanInput($request->input('company_name')),
                'address' => $this->cleanInput($request->input('address')),
                'shipping_address' => $this->cleanInput($request->input('shipping_address')),
                'postal_code' => $this->cleanInput($request->input('postal_code')),
                'state' => $this->cleanInput($request->input('state')),
                'city' => $this->cleanInput($request->input('city')),
                'office' => $this->cleanInput($request->input('office')),
                'cell' => $this->cleanInput($request->input('cell')),
                'website' => $this->cleanInput($request->input('website')),
                'note' => $this->cleanInput($request->input('note')),
                'client_type' => $this->cleanInput($request->input('client_type')),
                'lead_source_id' => $request->has('lead_source_id')
                    ? $this->resolveInteger($request->input('lead_source_id'), null)
                    : $client->clientDetails?->lead_source_id,
                'lead_category_id' => $request->has('lead_category_id')
                    ? $this->resolveInteger($request->input('lead_category_id'), null)
                    : $client->clientDetails?->lead_category_id,
                'lead_status_id' => $request->has('lead_status_id')
                    ? $this->resolveInteger($request->input('lead_status_id'), null)
                    : $client->clientDetails?->lead_status_id,
                'lead_interest_level' => $this->cleanInput($request->input('lead_interest_level')),
                'lead_deal_size' => $this->normalizeDecimal($request->input('lead_deal_size')),
                'lead_contact_status' => $this->cleanInput($request->input('lead_contact_status')),
                'lead_contact_status_reason' => $this->cleanInput($request->input('lead_contact_status_reason')),
                'products_services' => $this->cleanInput($request->input('products_services')),
                'last_contact_date' => $this->normalizeDate($request->input('last_contact_date')),
                'next_followup_date' => $this->normalizeDate($request->input('next_followup_date')),
                'skype' => $this->cleanInput($request->input('skype')),
                'facebook' => $this->cleanInput($request->input('facebook')),
                'twitter' => $this->cleanInput($request->input('twitter')),
                'linkedin' => $this->cleanInput($request->input('linkedin')),
                'tax_name' => $this->cleanInput($request->input('tax_name')),
                'gst_number' => $this->cleanInput($request->input('gst_number')),
                'electronic_address' => $this->cleanInput($request->input('electronic_address')),
                'electronic_address_scheme' => $this->cleanInput($request->input('electronic_address_scheme')),
            ]
        );

        $client->refresh()->load([
            'clientDetails.addedBy:id,name',
            'convertedLead.addedBy:id,name',
            'country:id,name',
        ]);

        return (new ClientResource($client))
            ->additional([
                'success' => true,
                'message' => 'Client updated successfully.',
            ])
            ->response();
    }

    public function tasks(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $viewPermission = $user->permission('view_tasks');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both'], true));

        $perPage = $this->perPage($request);
        $query = $this->visibleTasksQuery($request);

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tasks.heading', 'like', "%{$search}%")
                    ->orWhere('tasks.task_short_code', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($project) use ($search) {
                        $project->where('project_name', 'like', "%{$search}%")
                            ->orWhere('project_short_code', 'like', "%{$search}%");
                    });
            });
        }

        $this->applyTaskStatusFilter($query, trim((string) $request->get('status', '')));

        return response()->json(
            $query->orderByDesc('tasks.created_at')->paginate($perPage)
        );
    }

    public function task(Request $request, int $taskId): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $viewPermission = $user->permission('view_tasks');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both'], true));

        $task = $this->visibleTaskDetailQuery($request)
            ->where('tasks.id', $taskId)
            ->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task fetched successfully.',
            'data' => $this->formatTaskDetail($task),
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $editPermission = $user->permission('edit_tasks');
        abort_403(!in_array($editPermission, ['all', 'added', 'owned', 'both'], true));

        $perPage = $this->perPage($request);
        $query = User::allEmployees(
            null,
            true,
            $editPermission === 'all' ? 'all' : null
        );

        if ($query instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $employees = $query;
        } else {
            $search = trim((string) $request->get('search', ''));
            if ($search !== '') {
                $query = $query->filter(function ($employee) use ($search) {
                    $text = strtolower($search);
                    return str_contains(strtolower((string) $employee->name), $text)
                        || str_contains(strtolower((string) $employee->email), $text);
                })->values();
            }

            $employees = new \Illuminate\Pagination\LengthAwarePaginator(
                $query->forPage((int) $request->get('page', 1), $perPage)->values(),
                $query->count(),
                $perPage,
                (int) $request->get('page', 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return response()->json($employees);
    }

    public function completeTask(Request $request, int $taskId): JsonResponse
    {
        $task = Task::withTrashed()->with('project', 'users')->findOrFail($taskId);
        $this->ensureTaskStatusPermission($task, 'change_status');

        $taskBoardColumn = TaskboardColumn::completeColumn();
        if (!$taskBoardColumn) {
            return response()->json([
                'success' => false,
                'message' => 'Completed column not found.',
            ], 422);
        }

        $task->board_column_id = $taskBoardColumn->id;
        $task->status = 'completed';
        $task->completed_on = now(company()->timezone)->format('Y-m-d');
        $task->saveQuietly();

        return response()->json([
            'success' => true,
            'message' => 'Task marked complete successfully.',
        ]);
    }

    public function reassignTask(Request $request, int $taskId): JsonResponse
    {
        $task = Task::with('project', 'users')->findOrFail($taskId);
        $this->ensureTaskMutationPermission($task, 'edit_tasks');

        $assignableEmployees = User::allEmployees(
            null,
            true,
            $request->user()->permission('edit_tasks') === 'all' ? 'all' : null
        )->pluck('id')->map(fn ($id) => (int) $id)->all();

        $validated = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', Rule::in($assignableEmployees)],
        ]);

        $userIds = array_values(array_map(
            'intval',
            array_filter((array) ($validated['user_ids'] ?? []), fn ($userId) => $userId !== null && $userId !== '')
        ));

        $task->users()->sync($userIds);

        return response()->json([
            'success' => true,
            'message' => 'Task reassigned successfully.',
        ]);
    }

    public function updateTask(Request $request, int $taskId): JsonResponse
    {
        $task = Task::with('project', 'users')->findOrFail($taskId);
        $this->ensureTaskMutationPermission($task, 'edit_tasks');

        $validated = $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $task->heading = $validated['heading'];
        $task->description = $validated['description'] ?? null;
        $task->priority = $validated['priority'] ?? $task->priority;
        $task->start_date = isset($validated['start_date']) ? Carbon::parse($validated['start_date']) : $task->start_date;
        $task->due_date = isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : $task->due_date;

        if (!empty($validated['status'])) {
            $taskBoardColumn = TaskboardColumn::where('slug', $validated['status'])->first();
            if ($taskBoardColumn) {
                $task->board_column_id = $taskBoardColumn->id;
                $task->status = $taskBoardColumn->slug === 'completed' ? 'completed' : $task->status;
                $task->completed_on = $taskBoardColumn->slug === 'completed'
                    ? now(company()->timezone)->format('Y-m-d')
                    : null;
            }
        }

        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully.',
        ]);
    }

    public function destroyTask(Request $request, int $taskId): JsonResponse
    {
        $task = Task::with('project', 'users')->findOrFail($taskId);
        $this->ensureTaskMutationPermission($task, 'delete_tasks');
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function bulkTaskAction(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $validated = $request->validate([
            'action_type' => ['required', 'in:complete,delete'],
            'task_ids' => ['required', 'array', 'min:1'],
            'task_ids.*' => ['integer'],
        ]);

        $taskIds = array_values(array_unique(array_map('intval', $validated['task_ids'])));
        $tasks = Task::with('project', 'users')->whereIn('id', $taskIds)->get();

        if ($validated['action_type'] === 'delete') {
            foreach ($tasks as $task) {
                $this->ensureTaskMutationPermission($task, 'delete_tasks');
            }

            foreach ($tasks as $task) {
                $task->delete();
            }
        } else {
            $taskBoardColumn = TaskboardColumn::completeColumn();
            abort_if(!$taskBoardColumn, 422, 'Completed column not found.');

            foreach ($tasks as $task) {
                $this->ensureTaskStatusPermission($task, 'change_status');
                $task->status = 'completed';
                $task->board_column_id = $taskBoardColumn->id;
                $task->completed_on = now(company()->timezone)->format('Y-m-d');
                $task->saveQuietly();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Bulk action completed successfully.',
        ]);
    }

    public function reportSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthorized');

        $clients = 0;
        if (in_array($user->permission('view_clients'), ['all', 'added', 'owned', 'both'], true)) {
            $clients = (clone $this->clientCountQuery($user))->count('users.id');
        }

        $tasksQuery = in_array($user->permission('view_tasks'), ['all', 'added', 'owned', 'both'], true)
            ? $this->visibleTasksQuery($request)
            : null;
        $completeColumn = TaskboardColumn::completeColumn();

        $totalTasks = $tasksQuery ? (clone $tasksQuery)->count('tasks.id') : 0;
        $completedTasks = $tasksQuery ? (clone $tasksQuery)
            ->where(function ($q) use ($completeColumn) {
                $q->where('tasks.status', 'completed');
                if ($completeColumn) {
                    $q->orWhere('tasks.board_column_id', $completeColumn->id);
                }
            })
            ->count('tasks.id') : 0;
        $overdueTasks = $tasksQuery ? (clone $tasksQuery)
            ->whereNotNull('tasks.due_date')
            ->where('tasks.due_date', '<', now(company()->timezone)->toDateString())
            ->when($completeColumn, fn ($q) => $q->where('tasks.board_column_id', '<>', $completeColumn->id))
            ->count('tasks.id') : 0;

        $leads = in_array($user->permission('view_lead'), ['all', 'added', 'owned', 'both'], true)
            ? $this->visibleLeadsQuery($user)->count()
            : 0;

        return response()->json([
            'data' => [
                'clients' => $clients,
                'leads' => $leads,
                'tasks_total' => $totalTasks,
                'tasks_completed' => $completedTasks,
                'tasks_pending' => max($totalTasks - $completedTasks, 0),
                'tasks_overdue' => $overdueTasks,
            ],
        ]);
    }

    private function visibleTasksQuery(Request $request): Builder
    {
        $query = Task::query()
            ->with([
                'project:id,project_name,client_id,deleted_at',
                'boardColumn:id,column_name,slug,label_color',
                'users:id,name',
            ])
            ->where(function ($q) {
                $q->whereDoesntHave('project')
                    ->orWhereHas('project', fn ($project) => $project->whereNull('deleted_at'));
            })
            ->select([
                'tasks.id',
                'tasks.heading',
                'tasks.task_short_code',
                'tasks.project_id',
                'tasks.board_column_id',
                'tasks.status',
                'tasks.start_date',
                'tasks.due_date',
                'tasks.priority',
                'tasks.added_by',
                'tasks.created_at',
                'tasks.is_private',
            ]);

        return $this->applyTaskAccessFilters($query, $request);
    }

    private function visibleTaskDetailQuery(Request $request): Builder
    {
        $query = Task::query()
            ->with([
                'project:id,project_name,project_short_code,client_id,deleted_at',
                'boardColumn:id,column_name,slug,label_color',
                'users:id,name,image',
                'comments.user:id,name,image',
                'notes.user:id,name,image',
                'files',
                'history.user:id,name,image',
                'history.boardColumn:id,column_name,slug,label_color',
                'history.subTask:id,task_id,title,status',
            ])
            ->withCount([
                'subtasks',
                'completedSubtasks',
                'incompleteSubtasks',
                'comments',
                'notes',
                'files',
                'history',
            ])
            ->where(function ($q) {
                $q->whereDoesntHave('project')
                    ->orWhereHas('project', fn ($project) => $project->whereNull('deleted_at'));
            })
            ->select([
                'tasks.id',
                'tasks.heading',
                'tasks.description',
                'tasks.task_short_code',
                'tasks.project_id',
                'tasks.board_column_id',
                'tasks.status',
                'tasks.priority',
                'tasks.start_date',
                'tasks.due_date',
                'tasks.added_by',
                'tasks.created_at',
                'tasks.updated_at',
                'tasks.is_private',
            ]);

        return $this->applyTaskAccessFilters($query, $request);
    }

    private function applyTaskAccessFilters(Builder $query, Request $request): Builder
    {
        $user = $request->user();
        $viewPermission = $user->permission('view_tasks');

        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('tasks.is_private', 0)
                    ->orWhere(function ($private) use ($user) {
                        $private->where('tasks.is_private', 1)
                            ->where(function ($owned) use ($user) {
                                $owned->where('tasks.added_by', $user->id)
                                    ->orWhereHas('users', fn ($taskUser) => $taskUser->where('users.id', $user->id));
                            });
                });
            });
        }

        if ($viewPermission === 'owned') {
            $query->where(function ($q) use ($user) {
                $q->whereHas('users', fn ($taskUser) => $taskUser->where('users.id', $user->id))
                    ->orWhereHas('mentionTask', fn ($mention) => $mention->where('user_id', $user->id));

                if ($user->hasRole('client')) {
                    $q->orWhereHas('project', fn ($project) => $project->where('client_id', $user->id));
                }
            });
        } elseif ($viewPermission === 'added') {
            $query->where(function ($q) use ($user) {
                $q->where('tasks.added_by', $user->id)
                    ->orWhereHas('mentionTask', fn ($mention) => $mention->where('user_id', $user->id));
            });
        } elseif ($viewPermission === 'both') {
            $query->where(function ($q) use ($user) {
                $q->where('tasks.added_by', $user->id)
                    ->orWhereHas('users', fn ($taskUser) => $taskUser->where('users.id', $user->id))
                    ->orWhereHas('mentionTask', fn ($mention) => $mention->where('user_id', $user->id));

                if ($user->hasRole('client')) {
                    $q->orWhereHas('project', fn ($project) => $project->where('client_id', $user->id));
                }
            });
        }

        return $query;
    }

    private function formatTaskDetail(Task $task): array
    {
        return [
            'id' => $task->id,
            'heading' => $task->heading,
            'description' => $task->description,
            'task_short_code' => $task->task_short_code,
            'priority' => $task->priority,
            'status' => $task->status,
            'is_private' => (int) $task->is_private,
            'start_date' => optional($task->start_date)->toDateTimeString(),
            'due_date' => optional($task->due_date)->toDateTimeString(),
            'created_at' => optional($task->created_at)->toDateTimeString(),
            'updated_at' => optional($task->updated_at)->toDateTimeString(),
            'project' => $task->project ? [
                'id' => $task->project->id,
                'project_name' => $task->project->project_name,
                'project_short_code' => $task->project->project_short_code,
                'client_id' => $task->project->client_id,
            ] : null,
            'boardColumn' => $task->boardColumn ? [
                'id' => $task->boardColumn->id,
                'column_name' => $task->boardColumn->column_name,
                'slug' => $task->boardColumn->slug,
                'label_color' => $task->boardColumn->label_color,
            ] : null,
            'users' => $task->users->map(fn ($assignedUser) => [
                'id' => $assignedUser->id,
                'name' => $assignedUser->name,
                'image' => $assignedUser->image,
            ])->values(),
            'notes' => $task->notes->map(fn ($note) => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => optional($note->created_at)->toDateTimeString(),
                'user' => $note->user ? [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                    'image' => $note->user->image,
                ] : null,
            ])->values(),
            'files' => $task->files->map(fn ($file) => [
                'id' => $file->id,
                'filename' => $file->filename,
                'description' => $file->description,
                'size' => $file->size,
                'file_url' => $file->file_url,
                'icon' => $file->icon,
                'file' => $file->file,
            ])->values(),
            'comments' => $task->comments->map(fn ($comment) => [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'created_at' => optional($comment->created_at)->toDateTimeString(),
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'image' => $comment->user->image,
                ] : null,
            ])->values(),
            'history' => $task->history->map(fn ($entry) => [
                'id' => $entry->id,
                'details' => $entry->details,
                'created_at' => optional($entry->created_at)->toDateTimeString(),
                'user' => $entry->user ? [
                    'id' => $entry->user->id,
                    'name' => $entry->user->name,
                    'image' => $entry->user->image,
                ] : null,
                'boardColumn' => $entry->boardColumn ? [
                    'id' => $entry->boardColumn->id,
                    'column_name' => $entry->boardColumn->column_name,
                    'slug' => $entry->boardColumn->slug,
                    'label_color' => $entry->boardColumn->label_color,
                ] : null,
                'subTask' => $entry->subTask ? [
                    'id' => $entry->subTask->id,
                    'title' => $entry->subTask->title,
                    'status' => $entry->subTask->status,
                ] : null,
            ])->values(),
            'counts' => [
                'subtasks' => $task->subtasks_count ?? 0,
                'completed_subtasks' => $task->completed_subtasks_count ?? 0,
                'incomplete_subtasks' => $task->incomplete_subtasks_count ?? 0,
                'comments' => $task->comments_count ?? 0,
                'notes' => $task->notes_count ?? 0,
                'files' => $task->files_count ?? 0,
                'history' => $task->history_count ?? 0,
            ],
        ];
    }

    private function ensureTaskStatusPermission(Task $task, string $permission): void
    {
        $user = request()->user();
        $taskUsers = $task->users->pluck('id')->toArray();
        $permissionValue = $user->permission($permission);

        abort_403(
            !(
                $permissionValue === 'all'
                || ($permissionValue === 'added' && $task->added_by == $user->id)
                || ($permissionValue === 'owned' && in_array($user->id, $taskUsers, true))
                || ($permissionValue === 'both' && (in_array($user->id, $taskUsers, true) || $task->added_by == $user->id))
                || ($task->project && $task->project->project_admin == $user->id)
            )
        );
    }

    private function ensureTaskMutationPermission(Task $task, string $permission): void
    {
        $user = request()->user();
        $taskUsers = $task->users->pluck('id')->toArray();
        $permissionValue = $user->permission($permission);

        abort_403(
            !(
                $permissionValue === 'all'
                || ($permissionValue === 'owned' && in_array($user->id, $taskUsers, true))
                || ($permissionValue === 'added' && $task->added_by == $user->id)
                || ($task->project && $task->project->project_admin == $user->id)
                || ($permissionValue === 'both' && (in_array($user->id, $taskUsers, true) || $task->added_by == $user->id))
            )
        );
    }

    private function applyTaskStatusFilter(Builder $query, string $status): void
    {
        if ($status === '' || $status === 'all') {
            return;
        }

        $completeColumn = TaskboardColumn::completeColumn();
        if ($status === 'completed') {
            $query->where(function ($q) use ($completeColumn) {
                $q->where('tasks.status', 'completed');
                if ($completeColumn) {
                    $q->orWhere('tasks.board_column_id', $completeColumn->id);
                }
            });
            return;
        }

        if ($status === 'overdue') {
            $query->whereNotNull('tasks.due_date')
                ->where('tasks.due_date', '<', now(company()->timezone)->toDateString());
        }

        if (in_array($status, ['pending', 'overdue'], true) && $completeColumn) {
            $query->where('tasks.board_column_id', '<>', $completeColumn->id);
        }
    }

    private function clientCountQuery($user): Builder
    {
        $query = User::withoutGlobalScope(ActiveScope::class)
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->leftJoin('client_details', 'users.id', '=', 'client_details.user_id')
            ->where('roles.name', 'client');

        $viewPermission = $user->permission('view_clients');
        if (in_array($viewPermission, ['added', 'both'], true)) {
            $query->where('client_details.added_by', $user->id);
        }

        if ($user->hasRole('client')) {
            $query->where('client_details.user_id', $user->id);
        }

        return $query;
    }

    private function cleanInput(?string $value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['active', 'inactive', 'deactive'], true)
            ? $normalized
            : 'active';
    }

    private function normalizePhone(?string $value): ?string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }
        return $trimmed;
    }

    private function normalizeDate(?string $value): ?string
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return $trimmed;
        }
    }

    private function resolveCountryId(?string $value, ?int $fallback = null): ?int
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return $fallback;
        }

        if (ctype_digit($trimmed)) {
            return (int) $trimmed;
        }

        $country = Country::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($trimmed)])
            ->orWhereRaw('LOWER(nicename) = ?', [strtolower($trimmed)])
            ->orWhereRaw('LOWER(iso) = ?', [strtolower($trimmed)])
            ->first();

        return $country?->id ?? $fallback;
    }

    private function resolveInteger(mixed $value, ?int $fallback = null): ?int
    {
        if ($value === null) {
            return $fallback;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return $fallback;
        }

        return is_numeric($trimmed) ? (int) $trimmed : $fallback;
    }

    private function normalizeDecimal(?string $value): ?string
    {
        $cleaned = $this->cleanInput($value);
        if ($cleaned === null) {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', $cleaned);
        return $normalized === '' ? null : $normalized;
    }

    private function visibleLeadsQuery($user): Builder
    {
        $query = Lead::query()->whereNull('archived_at');
        $viewPermission = $user->permission('view_lead');

        if (!$user->hasRole('admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('added_by', $user->id)
                    ->orWhere('assigned_to', $user->id);
            });
        } elseif ($viewPermission === 'added') {
            $query->where('added_by', $user->id);
        } elseif ($viewPermission === 'owned') {
            $query->where('assigned_to', $user->id);
        } elseif ($viewPermission === 'both') {
            $query->where(function ($q) use ($user) {
                $q->where('added_by', $user->id)
                    ->orWhere('assigned_to', $user->id);
            });
        }

        return $query;
    }

    private function perPage(Request $request): int
    {
        return max(10, min((int) $request->get('per_page', 20), 50));
    }
}
