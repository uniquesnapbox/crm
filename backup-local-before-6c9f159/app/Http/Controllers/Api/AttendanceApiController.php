<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeLocation;
use App\Services\Attendance\AttendanceGeofenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AttendanceApiController extends Controller
{
    public function __construct(private AttendanceGeofenceService $geofenceService)
    {
    }

    public function clockIn(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'timestamp' => ['nullable', 'date'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $user = $request->user();

        try {
            $isValidLocation = $this->geofenceService->validateClockInLocation(
                $user,
                (float)$data['latitude'],
                (float)$data['longitude']
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'location' => $exception->getMessage(),
            ]);
        }

        if (!$isValidLocation) {
            throw ValidationException::withMessages([
                'location' => __('messages.notAnValidLocation'),
            ]);
        }

        $clockInTime = isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : now();
        $openAttendance = Attendance::where('user_id', $user->id)
            ->whereNull('clock_out_time')
            ->latest('clock_in_time')
            ->first();

        if ($openAttendance) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.attendanceMarked'),
            ], 422);
        }

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->company_id = $user->company_id;
        $attendance->clock_in_time = $clockInTime;
        $attendance->clock_in_ip = $request->ip();
        $attendance->working_from = 'Mobile App';
        $attendance->work_from_type = $this->geofenceService->shouldApplyOfficeGeofence($user) ? 'office' : 'other';
        $attendance->late = 'no';
        $attendance->half_day = 'no';
        $attendance->latitude = $data['latitude'];
        $attendance->longitude = $data['longitude'];
        $attendance->save();

        EmployeeLocation::create([
            'company_id' => $user->company_id,
            'employee_id' => $user->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'timestamp' => $clockInTime,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.attendanceClockInSuccess'),
            'data' => [
                'attendance_id' => $attendance->id,
                'time' => $clockInTime->toIso8601String(),
            ],
        ]);
    }

    public function clockOut(Request $request)
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'timestamp' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $clockOutTime = isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereNull('clock_out_time')
            ->latest('clock_in_time')
            ->firstOrFail();

        $attendance->clock_out_time = $clockOutTime;
        $attendance->clock_out_ip = $request->ip();
        $attendance->save();

        EmployeeLocation::create([
            'company_id' => $user->company_id,
            'employee_id' => $user->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'timestamp' => $clockOutTime,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.attendanceClockOutSuccess'),
        ]);
    }

    public function locationUpdate(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'timestamp' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        if ((int)$data['employee_id'] !== (int)$user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        $timestamp = isset($data['timestamp']) ? Carbon::parse($data['timestamp']) : now();

        $location = EmployeeLocation::create([
            'company_id' => $user->company_id,
            'employee_id' => $data['employee_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'timestamp' => $timestamp,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location updated successfully.',
            'data' => [
                'id' => $location->id,
                'timestamp' => $location->timestamp?->toIso8601String(),
            ],
        ]);
    }
}
