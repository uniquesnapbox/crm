<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\EmployeeResource;
use App\Models\User;
use App\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private function employeesQuery(Request $request): Builder
    {
        $query = User::query()
            ->withoutGlobalScope(ActiveScope::class)
            ->onlyEmployee()
            ->with([
                'roles:id,name,display_name',
                'employeeDetail:id,user_id,employee_id',
            ])
            ->select(['id', 'name', 'email', 'status', 'image']);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function (Builder $builder) use ($search) {
                $like = '%' . $search . '%';

                $builder->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('employeeDetail', function (Builder $employeeDetailQuery) use ($like) {
                        $employeeDetailQuery->where('employee_id', 'like', $like);
                    });
            });
        }

        if ($status = trim((string) $request->query('status', ''))) {
            $query->where('status', $status);
        }

        if ($role = trim((string) $request->query('role', ''))) {
            $query->whereHas('roles', function (Builder $roleQuery) use ($role) {
                $roleQuery->where('name', $role)
                    ->orWhere('display_name', $role);
            });
        }

        return $query->orderBy('name');
    }

    public function index(Request $request)
    {
        $defaultPerPage = max((int) config('integration_api.default_per_page', 12), 1);
        $maxPerPage = max((int) config('integration_api.max_per_page', 50), $defaultPerPage);
        $perPage = min(max((int) $request->integer('per_page', $defaultPerPage), 1), $maxPerPage);

        $employees = $this->employeesQuery($request)
            ->paginate($perPage)
            ->appends($request->query());

        return EmployeeResource::collection($employees)->additional([
            'success' => true,
            'message' => 'Employees fetched successfully.',
        ]);
    }

    public function show(Request $request, int $employeeId)
    {
        $employee = $this->employeesQuery($request)->whereKey($employeeId)->first();

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found.',
            ], 404);
        }

        return (new EmployeeResource($employee))->additional([
            'success' => true,
            'message' => 'Employee fetched successfully.',
        ]);
    }
}