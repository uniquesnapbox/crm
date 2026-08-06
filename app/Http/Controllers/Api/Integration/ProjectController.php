<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\ProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private function projectsQuery(Request $request): Builder
    {
        $query = Project::query()
            ->with([
                'client:id,name,email',
                'members.user:id,name,email,image',
            ])
            ->select([
                'projects.id',
                'projects.project_name',
                'projects.project_summary',
                'projects.client_id',
                'projects.project_admin',
                'projects.start_date',
                'projects.deadline',
                'projects.completion_percent',
                'projects.status',
                'projects.project_budget',
                'projects.hours_allocated',
                'projects.added_by',
                'projects.created_at',
                'projects.updated_at',
            ]);

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%' . $search . '%';

            $query->where(function (Builder $builder) use ($like) {
                $builder->where('projects.project_name', 'like', $like)
                    ->orWhere('projects.project_summary', 'like', $like)
                    ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                        $clientQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        if ($status = trim((string) $request->query('status', ''))) {
            $query->where('projects.status', $status);
        }

        if ($clientId = (int) $request->query('client_id', 0)) {
            $query->where('projects.client_id', $clientId);
        }

        if ($projectAdmin = (int) $request->query('project_admin', 0)) {
            $query->where('projects.project_admin', $projectAdmin);
        }

        return $query->orderBy('projects.project_name');
    }

    public function index(Request $request)
    {
        $defaultPerPage = max((int) config('integration_api.default_per_page', 12), 1);
        $maxPerPage = max((int) config('integration_api.max_per_page', 50), $defaultPerPage);
        $perPage = min(max((int) $request->integer('per_page', $defaultPerPage), 1), $maxPerPage);

        $projects = $this->projectsQuery($request)
            ->paginate($perPage)
            ->appends($request->query());

        return ProjectResource::collection($projects)->additional([
            'success' => true,
            'message' => 'Projects fetched successfully.',
        ]);
    }

    public function show(Request $request, int $projectId)
    {
        $project = $this->projectsQuery($request)->whereKey($projectId)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found.',
            ], 404);
        }

        return (new ProjectResource($project))->additional([
            'success' => true,
            'message' => 'Project fetched successfully.',
        ]);
    }
}
