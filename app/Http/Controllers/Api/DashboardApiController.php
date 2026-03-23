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

        // total employees
        $totalEmployees = User::whereHas('roles', function ($q) {
            $q->where('name', 'employee');
        })->count();

        // latest location per employee today
        $latest = EmployeeLocation::whereDate('clock_in_at', $today)
            ->select('employee_id', 'is_active', 'clock_out_at')
            ->get()
            ->groupBy('employee_id')
            ->map(function ($rows) {
                return $rows->sortByDesc('clock_in_at')->first();
            });

        $active = $latest->filter(function ($row) {
            return $row && $row->is_active;
        })->count();

        $completed = $latest->filter(function ($row) {
            return $row && !$row->is_active && $row->clock_out_at !== null;
        })->count();

        return response()->json([
            'date' => $today->toDateString(),
            'total_employees' => $totalEmployees,
            'active_employees' => $active,
            'completed_employees' => $completed,
        ]);
    }
}
