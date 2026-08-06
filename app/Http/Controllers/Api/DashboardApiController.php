<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardApiController extends Controller
{
    public function stats(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $today = Carbon::today();
        $startOfDay = $today->copy()->startOfDay();
        $startOfTomorrow = $today->copy()->addDay()->startOfDay();

        // total employees
        $totalEmployees = User::whereHas('roles', function ($q) {
            $q->where('name', 'employee');
        })->count();

        $latestLocationIds = EmployeeLocation::query()
            ->where('clock_in_at', '>=', $startOfDay)
            ->where('clock_in_at', '<', $startOfTomorrow)
            ->selectRaw('MAX(id)')
            ->groupBy('employee_id');

        $attendanceSummary = EmployeeLocation::query()
            ->whereIn('id', $latestLocationIds)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active_count,
                COALESCE(SUM(CASE WHEN is_active = 0 AND clock_out_at IS NOT NULL THEN 1 ELSE 0 END), 0) as completed_count
            ')
            ->first();

        $active = (int) ($attendanceSummary->active_count ?? 0);
        $completed = (int) ($attendanceSummary->completed_count ?? 0);

        return response()->json([
            'date' => $today->toDateString(),
            'total_employees' => $totalEmployees,
            'active_employees' => $active,
            'completed_employees' => $completed,
        ]);
    }
}
