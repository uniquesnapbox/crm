<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->where('roles.name', 'client')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.mobile',
                'users.status',
                'users.created_at',
                'client_details.company_name',
                'client_details.added_by',
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
        $user = $request->user();
        $viewPermission = $user->permission('view_tasks');

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
                'tasks.added_by',
                'tasks.created_at',
                'tasks.is_private',
            ]);

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
