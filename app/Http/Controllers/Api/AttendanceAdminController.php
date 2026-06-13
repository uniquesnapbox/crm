<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $isAdmin = $user->hasRole('admin');
        $employeeId = $request->filled('employee_id')
            ? (int) $request->employee_id
            : null;

        if (!$isAdmin) {
            // allow employees to fetch only their own records
            if (!$user->hasRole('employee')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if ($employeeId !== null && $employeeId !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $employeeId = $user->id; // default to self
        }

        $query = Attendance::with([
            'user:id,name',
            'tracking',
        ])->orderByDesc('clock_in_time');

        if ($employeeId !== null) {
            $query->where('user_id', $employeeId);
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'open') {
                $query->whereNull('clock_out_time');
            } elseif ($status === 'closed') {
                $query->whereNotNull('clock_out_time');
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('clock_in_time', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('clock_in_time', '<=', $request->to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page = $query->paginate($perPage);

        $data = $page->getCollection()->map(function ($attendance) {
            $location = $attendance->tracking;

            return [
                'id' => $attendance->id,
                'employee_id' => $attendance->user_id,
                'employee_name' => $attendance->user?->name,
                'employee_email' => $attendance->user?->email,
                'employee_phone' => $attendance->user?->mobile,
                'clock_in_time' => optional($attendance->clock_in_time)->toIso8601String(),
                'clock_out_time' => optional($attendance->clock_out_time)->toIso8601String(),
                'clock_in_address' => $location?->clock_in_address,
                'clock_out_address' => $location?->clock_out_address,
                'clock_in_photo' => $location?->clock_in_photo_path,
                'clock_out_photo' => $location?->clock_out_photo_path,
                'last_latitude' => $location?->last_latitude,
                'last_longitude' => $location?->last_longitude,
                'last_address' => $location?->last_address,
                'last_update_at' => optional($location?->last_update_at)->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ]);
    }
}
