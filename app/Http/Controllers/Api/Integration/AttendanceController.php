<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\AttendanceResource;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AttendanceController extends Controller
{
    private function hasStatusColumn(): bool
    {
        return Schema::hasColumn('attendances', 'status');
    }

    private function hasDateColumn(): bool
    {
        return Schema::hasColumn('attendances', 'date');
    }

    private function attendanceQuery(Request $request): Builder
    {
        $hasStatusColumn = $this->hasStatusColumn();
        $hasDateColumn = $this->hasDateColumn();

        $select = [
            'attendances.id',
            'attendances.user_id',
            'attendances.clock_in_time',
            'attendances.clock_out_time',
            'attendances.working_from',
            'attendances.work_from_type',
            'attendances.late',
            'attendances.half_day',
            'attendances.latitude',
            'attendances.longitude',
            'attendances.location_id',
            'attendances.employee_shift_id',
            'attendances.created_at',
            'attendances.updated_at',
        ];

        if ($hasStatusColumn) {
            $select[] = 'attendances.status';
        }

        if ($hasDateColumn) {
            $select[] = 'attendances.date';
        }

        $query = Attendance::query()
            ->with([
                'user:id,name,email,image,status',
                'location:id,location',
                'shift:id,shift_name',
            ])
            ->select($select);

        if ($hasDateColumn) {
            $query->orderByDesc('attendances.date');
        }

        $query
            ->orderByDesc('attendances.clock_in_time');

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%' . $search . '%';

            $query->whereHas('user', function (Builder $userQuery) use ($like) {
                $userQuery->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if ($employeeId = (int) $request->query('employee_id', 0)) {
            $query->where('attendances.user_id', $employeeId);
        }

        if ($hasStatusColumn && ($status = trim((string) $request->query('status', '')))) {
            $query->where('attendances.status', $status);
        }

        if ($hasDateColumn && ($date = trim((string) $request->query('date', '')))) {
            $query->whereDate('attendances.date', $date);
        }
        elseif ($date = trim((string) $request->query('date', ''))) {
            $query->whereDate('attendances.clock_in_time', $date);
        }

        if ($hasDateColumn && ($dateFrom = trim((string) $request->query('date_from', '')))) {
            $query->whereDate('attendances.date', '>=', $dateFrom);
        }
        elseif ($dateFrom = trim((string) $request->query('date_from', ''))) {
            $query->whereDate('attendances.clock_in_time', '>=', $dateFrom);
        }

        if ($hasDateColumn && ($dateTo = trim((string) $request->query('date_to', '')))) {
            $query->whereDate('attendances.date', '<=', $dateTo);
        }
        elseif ($dateTo = trim((string) $request->query('date_to', ''))) {
            $query->whereDate('attendances.clock_in_time', '<=', $dateTo);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $defaultPerPage = max((int) config('integration_api.default_per_page', 12), 1);
        $maxPerPage = max((int) config('integration_api.max_per_page', 50), $defaultPerPage);
        $perPage = min(max((int) $request->integer('per_page', $defaultPerPage), 1), $maxPerPage);

        $attendance = $this->attendanceQuery($request)
            ->paginate($perPage)
            ->appends($request->query());

        return AttendanceResource::collection($attendance)->additional([
            'success' => true,
            'message' => 'Attendance records fetched successfully.',
        ]);
    }

    public function show(Request $request, int $attendanceId)
    {
        $attendance = $this->attendanceQuery($request)->whereKey($attendanceId)->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found.',
            ], 404);
        }

        return (new AttendanceResource($attendance))->additional([
            'success' => true,
            'message' => 'Attendance record fetched successfully.',
        ]);
    }
}
