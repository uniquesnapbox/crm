<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveTrackingController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.liveTracking';

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('admin', user_roles()));

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $this->locations = $this->latestLocations();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->locationResponse();
        }

        return view('admin.live-tracking', $this->data);
    }

    protected function latestLocations()
    {
        $latestTimestamps = EmployeeLocation::query()
            ->selectRaw('employee_id, MAX(`timestamp`) as latest_timestamp')
            ->groupBy('employee_id');

        return EmployeeLocation::query()
            ->joinSub($latestTimestamps, 'latest_locations', function ($join) {
                $join->on('employee_locations.employee_id', '=', 'latest_locations.employee_id')
                    ->on('employee_locations.timestamp', '=', 'latest_locations.latest_timestamp');
            })
            ->join('users', 'users.id', '=', 'employee_locations.employee_id')
            ->where('users.company_id', user()->company_id)
            ->with('employee')
            ->orderByDesc('employee_locations.timestamp')
            ->get([
                'employee_locations.employee_id',
                'employee_locations.latitude',
                'employee_locations.longitude',
                'employee_locations.timestamp',
            ]);
    }

    protected function locationResponse(): JsonResponse
    {
        return response()->json([
            'locations' => $this->locations->map(function (EmployeeLocation $location) {
                return [
                    'employee_id' => $location->employee_id,
                    'employee_name' => optional($location->employee)->name,
                    'employee_image' => optional($location->employee)->image_url,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'timestamp' => optional($location->timestamp)->toIso8601String(),
                    'last_update_time' => optional($location->timestamp)->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format),
                ];
            })->values(),
        ]);
    }
}
