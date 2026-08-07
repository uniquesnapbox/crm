<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeLocation;
use App\Models\Lead;
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

        $company = company();
        if (!$company) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $timezone = $company->timezone ?? config('app.timezone');
        $companyId = $company->id;
        $today = now($timezone)->startOfDay();
        $startOfDay = $today->copy()->setTimezone('UTC');
        $startOfTomorrow = $today->copy()->addDay()->setTimezone('UTC');
        $startOfMonth = now($timezone)->startOfMonth()->startOfDay();
        $startOfNextMonth = $startOfMonth->copy()->addMonth();

        // total employees
        $totalEmployees = User::whereHas('roles', function ($q) {
            $q->where('name', 'employee');
        })->count();

        $latestLocationIds = EmployeeLocation::query()
            ->join('users as location_user', 'location_user.id', '=', 'employee_locations.employee_id')
            ->join('role_user', 'role_user.user_id', '=', 'location_user.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('location_user.company_id', $companyId)
            ->where('roles.name', 'employee')
            ->where('clock_in_at', '>=', $startOfDay)
            ->where('clock_in_at', '<', $startOfTomorrow)
            ->selectRaw('MAX(employee_locations.id)')
            ->groupBy('employee_locations.employee_id');

        $attendanceSummary = EmployeeLocation::query()
            ->whereIn('id', $latestLocationIds)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active_count,
                COALESCE(SUM(CASE WHEN is_active = 0 AND clock_out_at IS NOT NULL THEN 1 ELSE 0 END), 0) as completed_count
            ')
            ->first();

        $active = (int) ($attendanceSummary->active_count ?? 0);
        $completed = (int) ($attendanceSummary->completed_count ?? 0);
        $monthlyRevenue = $this->currentMonthConvertedRevenue(
            $companyId,
            $startOfMonth,
            $startOfNextMonth
        );

        return response()->json([
            'date' => $today->toDateString(),
            'total_employees' => $totalEmployees,
            'active_employees' => $active,
            'completed_employees' => $completed,
            'revenue' => $monthlyRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'collections' => $monthlyRevenue,
            'forecast_revenue' => $monthlyRevenue,
            'revenue_forecast' => $monthlyRevenue,
        ]);
    }

    private function currentMonthConvertedRevenue(
        int $companyId,
        Carbon $startOfMonth,
        Carbon $startOfNextMonth
    ): float {
        $startUtc = $startOfMonth->copy()->setTimezone('UTC')->toDateTimeString();
        $endUtc = $startOfNextMonth->copy()->setTimezone('UTC')->toDateTimeString();

        $revenue = Lead::query()
            ->join('users as client_user', 'client_user.id', '=', 'leads.client_id')
            ->leftJoin('client_details', function ($join) use ($companyId) {
                $join->on('client_details.user_id', '=', 'client_user.id')
                    ->where('client_details.company_id', '=', $companyId);
            })
            ->where('leads.company_id', $companyId)
            ->where('client_user.company_id', $companyId)
            ->where('client_user.status', 'active')
            ->whereNotNull('leads.client_id')
            ->whereNotNull('leads.converted_at')
            ->where('leads.converted_at', '>=', $startUtc)
            ->where('leads.converted_at', '<', $endUtc)
            ->selectRaw(
                'COALESCE(SUM(COALESCE(client_details.lead_deal_size, leads.deal_size, 0)), 0) as monthly_revenue'
            )
            ->value('monthly_revenue');

        return (float) ($revenue ?? 0);
    }
}
