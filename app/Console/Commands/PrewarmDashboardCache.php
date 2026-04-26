<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Company;
use App\Models\DashboardWidget;
use App\Models\EmployeeDetails;
use App\Models\EmployeeShiftSchedule;
use App\Models\Holiday;
use App\Models\LeadAgent;
use App\Models\Leave;
use App\Models\Notice;
use App\Models\ProjectTimeLog;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\Ticket;
use App\Models\TicketAgentGroups;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PrewarmDashboardCache extends Command
{
    protected $signature = 'dashboard:prewarm-cache {--company= : Prewarm a single company id} {--users=20 : Max active employees per company}';
    protected $description = 'Prewarm heavy employee dashboard caches to reduce cold start latency';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $userLimit = max(1, (int) $this->option('users'));

        $companies = Company::query()
            ->select('id', 'timezone', 'status')
            ->where('status', 'active');

        if (!empty($companyId)) {
            $companies->where('id', (int) $companyId);
        }

        $companies = $companies->get();

        if ($companies->isEmpty()) {
            $this->warn('No active company found for dashboard cache prewarm.');

            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            $this->prewarmForCompany($company->id, $company->timezone, $userLimit);
        }

        $this->info('Dashboard cache prewarm completed.');

        return self::SUCCESS;
    }

    private function prewarmForCompany(int $companyId, string $timezone, int $userLimit): void
    {
        $today = now($timezone)->toDateString();
        $todayStart = now($timezone)->startOfDay()->setTimezone('UTC')->toDateTimeString();
        $todayEnd = now($timezone)->endOfDay()->setTimezone('UTC')->toDateTimeString();
        $currentDayMonth = now($timezone)->format('m-d');

        $dashboardTtl = now()->addSeconds(60);
        $widgetsTtl = now()->addMinutes(1);
        $metaTtl = now()->addMinutes(2);

        $userCardRelations = function ($query) {
            return $query->without(['clientDetails', 'leaves', 'roles'])
                ->select('id', 'name', 'image', 'salutation', 'status')
                ->with([
                    'session:id,user_id,last_activity',
                    'employeeDetail:id,user_id,designation_id',
                    'employeeDetail.designation:id,name',
                ]);
        };

        $attendanceSetting = AttendanceSetting::query()->where('company_id', $companyId)->first();
        if ($attendanceSetting) {
            Cache::put('attendance_setting_' . $companyId, $attendanceSetting, $widgetsTtl);
        }

        $widgets = DashboardWidget::query()
            ->where('company_id', $companyId)
            ->where('dashboard_type', 'private-dashboard')
            ->get();
        Cache::put('private_dashboard_widgets_' . $companyId, $widgets, $widgetsTtl);

        $completedTaskColumnId = TaskboardColumn::query()
            ->where('company_id', $companyId)
            ->where('slug', 'completed')
            ->value('id');

        if (!$completedTaskColumnId) {
            $completedTaskColumnId = TaskboardColumn::query()
                ->where('company_id', $companyId)
                ->orderBy('priority')
                ->value('id');
        }

        if ($completedTaskColumnId) {
            Cache::put('task_complete_column_' . $companyId, (int) $completedTaskColumnId, $metaTtl);
        }

        $upcomingBirthdays = EmployeeDetails::query()
            ->where('company_id', $companyId)
            ->whereHas('user', fn($query) => $query->where('status', 'active'))
            ->with(['user' => $userCardRelations])
            ->select('employee_details.id', 'employee_details.user_id', 'employee_details.date_of_birth')
            ->whereNotNull('date_of_birth')
            ->whereRaw('DATE_FORMAT(`date_of_birth`, "%m-%d") >= ?', [$currentDayMonth])
            ->orderByRaw('MONTH(date_of_birth), DAY(date_of_birth)')
            ->limit(5)
            ->get()
            ->values()
            ->all();
        Cache::put('employee_dashboard_birthdays_' . $companyId . '_' . $today, $upcomingBirthdays, $dashboardTtl);

        $upcomingAnniversaries = EmployeeDetails::query()
            ->where('company_id', $companyId)
            ->whereHas('user', fn($query) => $query->where('status', 'active'))
            ->with(['user' => $userCardRelations])
            ->select('employee_details.id', 'employee_details.user_id', 'employee_details.joining_date')
            ->whereNotNull('joining_date')
            ->whereRaw('DATE_FORMAT(`joining_date`, "%m-%d") = ?', [$currentDayMonth])
            ->orderByRaw('MONTH(joining_date), DAY(joining_date)')
            ->get()
            ->values()
            ->all();
        Cache::put('employee_dashboard_anniversaries_' . $companyId . '_' . $today, $upcomingAnniversaries, $dashboardTtl);

        $leaveToday = Leave::query()
            ->where('company_id', $companyId)
            ->with([
                'user' => $userCardRelations,
                'type:id,type_name,color',
            ])
            ->where('status', 'approved')
            ->where('leave_date', now($timezone)->toDateString())
            ->get();
        Cache::put('employee_dashboard_leave_today_' . $companyId . '_' . $today, $leaveToday, $dashboardTtl);

        $workFromHome = Attendance::query()
            ->where('company_id', $companyId)
            ->with(['user' => $userCardRelations])
            ->select('id', 'user_id')
            ->where('work_from_type', 'home')
            ->whereBetween('attendances.clock_in_time', [$todayStart, $todayEnd])
            ->groupBy('user_id')
            ->get();
        Cache::put('employee_dashboard_wfh_' . $companyId . '_' . $today, $workFromHome, $dashboardTtl);

        $userIds = DB::table('users')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('users.company_id', $companyId)
            ->where('users.status', 'active')
            ->where('roles.name', 'employee')
            ->select('users.id')
            ->distinct()
            ->limit($userLimit)
            ->pluck('users.id');

        foreach ($userIds as $userId) {
            $employeeDetails = EmployeeDetails::query()
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->select('department_id', 'designation_id', 'employment_type')
                ->first();
            $departmentId = $employeeDetails?->department_id;

            $currentClockIn = Attendance::query()
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->whereBetween('clock_in_time', [$todayStart, $todayEnd])
                ->whereNull('clock_out_time')
                ->select('id', 'clock_in_time', 'clock_out_time')
                ->first();
            Cache::put('employee_dashboard_current_clock_in_' . $companyId . '_' . $userId . '_' . $today, $currentClockIn, $dashboardTtl);

            $todayLeave = Leave::query()
                ->where('company_id', $companyId)
                ->where('status', 'approved')
                ->where('user_id', $userId)
                ->where('duration', '<>', 'half day')
                ->where('leave_date', now($timezone)->toDateString())
                ->select('id')
                ->first();
            Cache::put('employee_dashboard_today_leave_' . $companyId . '_' . $userId . '_' . $today, $todayLeave, $dashboardTtl);

            $todayHoliday = null;
            if ($employeeDetails) {
                $todayHoliday = Holiday::query()
                    ->where('company_id', $companyId)
                    ->where('date', $today)
                    ->where(function ($query) use ($employeeDetails) {
                        $query->where('department_id_json', 'like', '%"' . $employeeDetails->department_id . '"%')
                            ->orWhereNull('department_id_json');
                    })
                    ->where(function ($query) use ($employeeDetails) {
                        $query->where('designation_id_json', 'like', '%"' . $employeeDetails->designation_id . '"%')
                            ->orWhereNull('designation_id_json');
                    })
                    ->where(function ($query) use ($employeeDetails) {
                        $query->where('employment_type_json', 'like', '%"' . $employeeDetails->employment_type . '"%')
                            ->orWhereNull('employment_type_json');
                    })
                    ->first();
            }
            Cache::put('employee_dashboard_today_holiday_' . $companyId . '_' . $userId . '_' . $today, $todayHoliday, $dashboardTtl);

            $activeTimer = ProjectTimeLog::query()
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->whereNull('end_time')
                ->with([
                    'task:id,heading',
                    'project:id,client_id',
                    'breaks:id,project_time_log_id,start_time,end_time,total_minutes',
                    'activeBreak:id,project_time_log_id,start_time,end_time',
                ])
                ->select('id', 'user_id', 'task_id', 'project_id', 'start_time', 'end_time', 'added_by')
                ->first();
            Cache::put('employee_dashboard_active_timer_' . $companyId . '_' . $userId, $activeTimer, now()->addSeconds(20));

            $leadAgent = LeadAgent::query()
                ->where('company_id', $companyId)
                ->where('user_id', $userId)
                ->first();
            Cache::put('employee_dashboard_lead_agent_' . $companyId . '_' . $userId, $leadAgent, now()->addMinutes(2));

            if ($attendanceSetting) {
                $weekStart = now($timezone)->startOfWeek($attendanceSetting->week_start_from)->format('Y-m-d');
                $weekEnd = now($timezone)->startOfWeek($attendanceSetting->week_start_from)->addDays(7)->format('Y-m-d');

                $weekShifts = EmployeeShiftSchedule::query()
                    ->where('user_id', $userId)
                    ->whereBetween('date', [$weekStart, $weekEnd])
                    ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'), 'employee_shift_schedules.*')
                    ->with('shift', 'requestChange')
                    ->get();
                Cache::put('employee_dashboard_week_shifts_' . $companyId . '_' . $userId . '_' . $weekStart, $weekShifts, $dashboardTtl);

                $weekLeaves = Leave::query()
                    ->where('company_id', $companyId)
                    ->with('type')
                    ->select(DB::raw('DATE_FORMAT(`leave_date`, "%Y-%m-%d") as ldate'), 'leaves.*')
                    ->where('user_id', $userId)
                    ->whereBetween('leave_date', [$weekStart, $weekEnd])
                    ->where('status', 'approved')
                    ->where('duration', '<>', 'half day')
                    ->get();
                Cache::put('employee_dashboard_week_leaves_' . $companyId . '_' . $userId . '_' . $weekStart, $weekLeaves, $dashboardTtl);
            }

            if ($completedTaskColumnId) {
                $stats = DB::table('users')
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT p.id) FROM projects p INNER JOIN project_members pm ON pm.project_id = p.id WHERE pm.user_id = ? AND p.company_id = ? AND p.completion_percent <> 100) AS total_projects',
                        [$userId, $companyId]
                    )
                    ->selectRaw(
                        '(SELECT IFNULL(SUM(ptl.total_minutes), 0) FROM project_time_logs ptl WHERE ptl.user_id = ? AND ptl.company_id = ?) AS total_hours_logged',
                        [$userId, $companyId]
                    )
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_users tu ON tu.task_id = t.id WHERE tu.user_id = ? AND t.company_id = ? AND t.board_column_id <> ?) AS in_process_tasks',
                        [$userId, $companyId, $completedTaskColumnId]
                    )
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_users tu ON tu.task_id = t.id WHERE tu.user_id = ? AND t.company_id = ? AND t.board_column_id <> ? AND t.due_date IS NOT NULL AND t.due_date < ?) AS due_tasks',
                        [$userId, $companyId, $completedTaskColumnId, $today]
                    )
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT p.id) FROM projects p INNER JOIN project_members pm ON pm.project_id = p.id WHERE pm.user_id = ? AND p.company_id = ? AND p.completion_percent <> 100 AND p.deadline IS NOT NULL AND p.deadline < ?) AS due_projects',
                        [$userId, $companyId, $today]
                    )
                    ->first();

                Cache::put('employee_dashboard_core_stats_' . $companyId . '_' . $userId . '_' . $today, $stats, $dashboardTtl);

                $pendingTasks = Task::query()
                    ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
                    ->where('tasks.company_id', $companyId)
                    ->where('task_users.user_id', $userId)
                    ->where('tasks.board_column_id', '<>', $completedTaskColumnId)
                    ->with([
                        'boardColumn:id,column_name,label_color',
                        'labels:id,label_name,label_color',
                    ])
                    ->select('tasks.id', 'tasks.task_short_code', 'tasks.heading', 'tasks.board_column_id', 'tasks.due_date')
                    ->distinct()
                    ->orderByDesc('tasks.id')
                    ->limit(20)
                    ->get();

                Cache::put('employee_dashboard_pending_tasks_' . $companyId . '_' . $userId . '_' . $today, $pendingTasks, $dashboardTtl);
            }

            $tickets = Ticket::query()
                ->where('company_id', $companyId)
                ->where(function ($query) {
                    $query->where('status', 'open')
                        ->orWhere('status', 'pending');
                })
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhere('agent_id', $userId);
                })
                ->select('id', 'ticket_number', 'subject', 'status', 'updated_at', 'user_id', 'agent_id')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
            Cache::put('employee_dashboard_tickets_' . $companyId . '_' . $userId, $tickets, $dashboardTtl);

            $isTicketAgent = TicketAgentGroups::query()
                ->where('company_id', $companyId)
                ->where('agent_id', $userId)
                ->exists();
            Cache::put('employee_dashboard_is_ticket_agent_' . $companyId . '_' . $userId, $isTicketAgent, $metaTtl);

            if ($isTicketAgent) {
                $openCount = Ticket::query()
                    ->where('company_id', $companyId)
                    ->where('status', 'open')
                    ->where('agent_id', $userId)
                    ->count();
                Cache::put('employee_dashboard_open_tickets_' . $companyId . '_' . $userId, $openCount, $dashboardTtl);
            }

            $noticeAdded = Notice::query()
                ->where('company_id', $companyId)
                ->where('added_by', $userId)
                ->latest()
                ->select('id', 'heading', 'created_at')
                ->limit(10)
                ->get();
            Cache::put('employee_dashboard_notices_added_' . $companyId . '_' . $userId, $noticeAdded, $dashboardTtl);

            $noticeOwned = Notice::query()
                ->where('company_id', $companyId)
                ->latest()
                ->select('id', 'heading', 'created_at')
                ->where(function ($query) use ($departmentId) {
                    $query->where(['to' => 'employee', 'department_id' => null]);
                    if (!is_null($departmentId)) {
                        $query->orWhere('department_id', $departmentId);
                    }
                })
                ->limit(10)
                ->get();
            Cache::put('employee_dashboard_notices_owned_' . $companyId . '_' . $userId . '_' . ($departmentId ?? 0), $noticeOwned, $dashboardTtl);

            $noticeBoth = Notice::query()
                ->where('company_id', $companyId)
                ->latest()
                ->select('id', 'heading', 'created_at')
                ->where(function ($query) use ($userId, $departmentId) {
                    $query->where('added_by', $userId)
                        ->orWhere(function ($inner) use ($departmentId) {
                            $inner->where(['to' => 'employee', 'department_id' => null]);
                            if (!is_null($departmentId)) {
                                $inner->orWhere('department_id', $departmentId);
                            }
                        });
                })
                ->limit(10)
                ->get();
            Cache::put('employee_dashboard_notices_both_' . $companyId . '_' . $userId . '_' . ($departmentId ?? 0), $noticeBoth, $dashboardTtl);
        }

        $noticeAll = Notice::query()
            ->where('company_id', $companyId)
            ->latest()
            ->select('id', 'heading', 'created_at')
            ->limit(10)
            ->get();
        Cache::put('employee_dashboard_notices_all_' . $companyId, $noticeAll, $dashboardTtl);

        if ($attendanceSetting) {
            $weekStart = now($timezone)->startOfWeek($attendanceSetting->week_start_from)->format('Y-m-d');
            $weekEnd = now($timezone)->startOfWeek($attendanceSetting->week_start_from)->addDays(7)->format('Y-m-d');
            $weekHolidays = Holiday::query()
                ->where('company_id', $companyId)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->select(DB::raw('DATE_FORMAT(`date`, "%Y-%m-%d") as hdate'), 'occassion')
                ->get();
            Cache::put('employee_dashboard_week_holidays_' . $companyId . '_' . $weekStart, $weekHolidays, $dashboardTtl);
        }

        $generalShift = Company::query()
            ->where('id', $companyId)
            ->with(['attendanceSetting', 'attendanceSetting.shift'])
            ->first();
        Cache::put('employee_dashboard_general_shift_' . $companyId, $generalShift, now()->addMinutes(5));
    }
}
