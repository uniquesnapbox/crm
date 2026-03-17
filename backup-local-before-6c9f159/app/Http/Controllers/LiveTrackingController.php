<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiveTrackingController extends AccountBaseController
{
    public function index(Request $request)
    {
        abort_403(!in_array('admin', user_roles()));

        if ($request->ajax() || $request->wantsJson() || $request->get('format') === 'json') {
            return response()->json([
                'status' => 'success',
                'data' => $this->trackingRows(),
                'office' => [
                    'latitude' => (float)(company()->defaultAddress?->latitude ?: company()->latitude),
                    'longitude' => (float)(company()->defaultAddress?->longitude ?: company()->longitude),
                ],
            ]);
        }

        $this->pageTitle = 'Live Employee Tracking';

        return view('admin.live-tracking', $this->data);
    }

    private function trackingRows()
    {
        $latestSubquery = EmployeeLocation::query()
            ->select('employee_id', DB::raw('MAX(`timestamp`) as latest_timestamp'))
            ->where('company_id', company()->id)
            ->groupBy('employee_id');

        return EmployeeLocation::query()
            ->joinSub($latestSubquery, 'latest_locations', function ($join) {
                $join->on('employee_locations.employee_id', '=', 'latest_locations.employee_id')
                    ->on('employee_locations.timestamp', '=', 'latest_locations.latest_timestamp');
            })
            ->join('users', 'users.id', '=', 'employee_locations.employee_id')
            ->leftJoin('employee_details', 'employee_details.user_id', '=', 'users.id')
            ->leftJoin('designations', 'designations.id', '=', 'employee_details.designation_id')
            ->where('employee_locations.company_id', company()->id)
            ->select([
                'employee_locations.employee_id',
                'employee_locations.latitude',
                'employee_locations.longitude',
                'employee_locations.timestamp',
                'users.name as employee_name',
                'designations.name as designation_name',
            ])
            ->orderBy('users.name')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => $row->employee_id,
                'employee_name' => $row->employee_name,
                'designation_name' => $row->designation_name,
                'latitude' => (float)$row->latitude,
                'longitude' => (float)$row->longitude,
                'timestamp' => optional($row->timestamp)->timezone(company()->timezone)?->toDateTimeString(),
            ])
            ->values();
    }
}
