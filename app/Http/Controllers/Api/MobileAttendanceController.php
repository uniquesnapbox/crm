<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MobileAttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'timestamp' => 'required|date',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance/selfies', 'public');
        }

        // Prevent multiple active clock-ins
        $existingActive = EmployeeLocation::where('employee_id', $user->id)
            ->where('is_active', true)
            ->first();
        if ($existingActive) {
            return response()->json([
                'message' => 'You are already clocked in. Please clock out first.',
                'employee_location_id' => $existingActive->id,
            ], 409);
        }

        // Create attendance row if you want to link it; otherwise skip.
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => Carbon::parse($request->timestamp)->timezone('UTC'),
            'clock_in_ip' => $request->ip(),
            'working_from' => 'mobile',
            'added_by' => $user->id,
        ]);

        $location = EmployeeLocation::updateOrCreate(
            [
                'employee_id' => $user->id,
                'is_active' => true,
            ],
            [
                'attendance_id' => $attendance->id,
                'clock_in_at' => Carbon::parse($request->timestamp),
                'clock_in_latitude' => $request->latitude,
                'clock_in_longitude' => $request->longitude,
                'clock_in_address' => $request->address,
                'clock_in_photo_path' => $photoPath,
                'last_update_at' => Carbon::parse($request->timestamp),
                'last_latitude' => $request->latitude,
                'last_longitude' => $request->longitude,
                'last_address' => $request->address,
                'timestamp' => Carbon::parse($request->timestamp),
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'ok',
            'attendance_id' => $attendance->id,
            'employee_location_id' => $location->id,
            'photo_path' => $photoPath,
        ]);
    }

    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'timestamp' => 'required|date',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('attendance/selfies', 'public');
        }

        $location = EmployeeLocation::where('employee_id', $user->id)
            ->where('is_active', true)
            ->latest('clock_in_at')
            ->first();

        if (!$location) {
            return response()->json(['message' => 'Active shift not found'], 404);
        }

        $location->update([
            'clock_out_at' => Carbon::parse($request->timestamp),
            'clock_out_latitude' => $request->latitude,
            'clock_out_longitude' => $request->longitude,
            'clock_out_address' => $request->address,
            'clock_out_photo_path' => $photoPath,
            'is_active' => false,
        ]);

        if ($location->attendance_id) {
            Attendance::whereKey($location->attendance_id)->update([
                'clock_out_time' => Carbon::parse($request->timestamp)->timezone('UTC'),
                'clock_out_ip' => $request->ip(),
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'employee_location_id' => $location->id,
            'photo_path' => $photoPath,
        ]);
    }

    public function liveUpdate(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'timestamp' => 'required|date',
            'address' => 'nullable|string',
        ]);

        $location = EmployeeLocation::firstOrCreate(
            ['employee_id' => $user->id, 'is_active' => true],
            [
                'attendance_id' => null,
                'clock_in_at' => Carbon::parse($request->timestamp),
                'clock_in_latitude' => $request->latitude,
                'clock_in_longitude' => $request->longitude,
                'clock_in_address' => $request->address,
                'last_update_at' => Carbon::parse($request->timestamp),
                'last_latitude' => $request->latitude,
                'last_longitude' => $request->longitude,
                'last_address' => $request->address,
                'timestamp' => Carbon::parse($request->timestamp),
                'is_active' => true,
            ]
        );

        $location->update([
            'last_update_at' => Carbon::parse($request->timestamp),
            'last_latitude' => $request->latitude,
            'last_longitude' => $request->longitude,
            'last_address' => $request->address,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
