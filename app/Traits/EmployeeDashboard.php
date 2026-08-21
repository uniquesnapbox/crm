<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\Task;
use App\Helper\Reply;
use App\Models\Event;
use App\Models\Leave;
use App\Models\Notice;
use App\Models\Ticket;
use App\Models\Holiday;
use Carbon\CarbonPeriod;
use App\Models\LeadAgent;
use App\Models\Attendance;
use App\Models\Appreciation;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\ProjectTimeLog;
use App\Models\DashboardWidget;
use App\Models\EmployeeDetails;
use App\Models\TaskboardColumn;
use App\Models\AttendanceSetting;
use App\Models\TicketAgentGroups;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectTimeLogBreak;
use App\Models\EmployeeShiftSchedule;
use App\Http\Requests\ClockIn\ClockInRequest;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;

/**
 *
 */
trait EmployeeDashboard
{

    /**
     * @return array|Application|Factory|View
     */
    public function employeeDashboard()
    {
        $user = user();
        $this->user = $user;
        $employeeDetail = $user->employeeDetail ?? $user->employeeDetails ?? null;
        $companyId = company()->id;
        $today = now(company()->timezone)->toDateString();
        $dashboardCacheTtl = now()->addSeconds(60);

        $completedTaskColumn = TaskboardColumn::completeColumn();
        $showClockIn = Cache::remember('attendance_setting_' . $companyId, now()->addMinutes(1), fn() => AttendanceSetting::first());

        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;

        $endTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;
        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        $officeStartTime = $officeStartTime->setTimezone('UTC');
        $officeEndTime = $officeEndTime->setTimezone('UTC');

        if ($officeStartTime->gt($officeEndTime)) {
            $officeEndTime->addDay();
        }

        $this->cannotLogin = Attendance::where('user_id', $user->id)
            ->whereBetween('clock_in_time', [$today . ' 00:00:00', $today . ' 23:59:59'])
            ->whereNotNull('clock_out_time')
            ->where('clock_in_time', '<=', now())
            ->where('clock_out_time', '>=', now())
            ->exists();

        if ($showClockIn->employee_clock_in_out == 'no' || $this->attendanceSettings->shift_name == 'Day Off') {
            $this->cannotLogin = true;
        }
        elseif (is_null($this->attendanceSettings->early_clock_in) && !now()->between($officeStartTime, $officeEndTime) && $showClockIn->show_clock_in_button == 'no') {
            $this->cannotLogin = true;
        }
        else {
            $earlyClockIn = now(company()->timezone)->addMinutes($this->attendanceSettings->early_clock_in)->setTimezone('UTC');

            if (!$earlyClockIn->gte($officeStartTime) && $showClockIn->show_clock_in_button == 'no') {
                $this->cannotLogin = true;
            }
            elseif ($this->cannotLogin && now()->betweenIncluded($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay())) {
                $this->cannotLogin = false;
            }
        }

        $currentDate = now();

        $this->checkJoiningDate = true;

        if (is_null($employeeDetail?->joining_date) || $employeeDetail->joining_date->gt($currentDate)) {
            $this->checkJoiningDate = false;
        }

        $this->viewEventPermission = $user->permission('view_events');
        $this->viewHolidayPermission = $user->permission('view_holiday');
        $this->viewTaskPermission = $user->permission('view_tasks');
        $this->viewTicketsPermission = $user->permission('view_tickets');
        $this->viewLeavePermission = $user->permission('view_leave');
        $this->viewNoticePermission = $user->permission('view_notice');
        $this->editTimelogPermission = $user->permission('edit_timelogs');
        $this->widgets = Cache::remember('private_dashboard_widgets_' . $companyId, now()->addMinutes(1), fn() => DashboardWidget::where('dashboard_type', 'private-dashboard')->get());
        $this->activeWidgets = $this->widgets->filter(fn($value) => $value->status == '1')->pluck('widget_name')->toArray();
        $dashboardUserCardRelations = function ($query) {
            return $query->without(['clientDetails', 'leaves', 'roles'])
                ->select('id', 'name', 'image', 'salutation', 'status')
                ->with([
                    'session:id,user_id,last_activity',
                    'employeeDetail:id,user_id,designation_id',
                    'employeeDetail.designation:id,name',
                ]);
        };

        // Getting Attendance setting data

        if (request('start') && request('end') && !is_null($this->viewEventPermission) && $this->viewEventPermission != 'none') {
            $eventData = array();

            $events = Event::with('attendee', 'attendee.user');

            if ($this->viewEventPermission == 'added') {
                $events->where('events.added_by', $this->user->id);
            }
            elseif ($this->viewEventPermission == 'owned' || $this->viewEventPermission == 'both') {
                $events->where('events.added_by', $this->user->id)
                    ->orWhere(function ($q) {
                        $q->whereHas('attendee.user', function ($query) {
                            $query->where('user_id', $this->user->id);
                        });
                    });
            }

            $events = $events->get();

            foreach ($events as $key => $event) {
                $eventData[] = [
                    'id' => $event->id,
                    'title' => $event->event_name,
                    'start' => $event->start_date_time,
                    'end' => $event->end_date_time,
                    'extendedProps' => ['bg_color' => $event->label_color, 'color' => '#fff'],
                ];
            }

            return $eventData;
        }

        $stats = Cache::remember(
            'employee_dashboard_core_stats_' . $companyId . '_' . $this->user->id . '_' . $today,
            $dashboardCacheTtl,
            function () use ($companyId, $completedTaskColumn, $today) {
                return DB::table('users')
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT p.id) FROM projects p INNER JOIN project_members pm ON pm.project_id = p.id WHERE pm.user_id = ? AND p.company_id = ? AND p.completion_percent <> 100) AS total_projects',
                        [$this->user->id, $companyId]
                    )
                    ->selectRaw(
                        '(SELECT IFNULL(SUM(ptl.total_minutes), 0) FROM project_time_logs ptl WHERE ptl.user_id = ? AND ptl.company_id = ?) AS total_hours_logged',
                        [$this->user->id, $companyId]
                    )
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_users tu ON tu.task_id = t.id WHERE tu.user_id = ? AND t.company_id = ? AND t.board_column_id <> ?) AS in_process_tasks',
                        [$this->user->id, $companyId, $completedTaskColumn->id]
                    )
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT t.id) FROM tasks t INNER JOIN task_users tu ON tu.task_id = t.id WHERE tu.user_id = ? AND t.company_id = ? AND t.board_column_id <> ? AND t.due_date IS NOT NULL AND t.due_date < ?) AS due_tasks',
                        [$this->user->id, $companyId, $completedTaskColumn->id, $today]
                    )
                    ->selectRaw(
                        '(SELECT COUNT(DISTINCT p.id) FROM projects p INNER JOIN project_members pm ON pm.project_id = p.id WHERE pm.user_id = ? AND p.company_id = ? AND p.completion_percent <> 100 AND p.deadline IS NOT NULL AND p.deadline < ?) AS due_projects',
                        [$this->user->id, $companyId, $today]
                    )
                    ->first();
            }
        );

        $this->totalProjects = (int) ($stats->total_projects ?? 0);
        $this->inProcessTasks = (int) ($stats->in_process_tasks ?? 0);
        $this->dueTasks = (int) ($stats->due_tasks ?? 0);
        $this->dueProjects = (int) ($stats->due_projects ?? 0);

        if (!is_null($this->viewNoticePermission) && $this->viewNoticePermission != 'none') {
            $departmentId = $employeeDetail?->department_id ?? 0;

            if ($this->viewNoticePermission == 'added') {
                $this->notices = Cache::remember('employee_dashboard_notices_added_' . $companyId . '_' . $this->user->id, $dashboardCacheTtl, function () {
                    return Notice::latest()->where('added_by', $this->user->id)
                        ->select('id', 'heading', 'created_at')
                        ->limit(10)
                        ->get();
                });
            }
            elseif ($this->viewNoticePermission == 'owned') {
                $this->notices = Cache::remember('employee_dashboard_notices_owned_' . $companyId . '_' . $this->user->id . '_' . $departmentId, $dashboardCacheTtl, function () use ($departmentId) {
                    return Notice::latest()
                        ->select('id', 'heading', 'created_at')
                        ->where(['to' => 'employee', 'department_id' => null])
                        ->orWhere(['department_id' => $departmentId])
                        ->limit(10)
                        ->get();
                });
            }
            elseif ($this->viewNoticePermission == 'both') {
                $this->notices = Cache::remember('employee_dashboard_notices_both_' . $companyId . '_' . $this->user->id . '_' . $departmentId, $dashboardCacheTtl, function () use ($departmentId) {
                    return Notice::latest()
                        ->select('id', 'heading', 'created_at')
                        ->where('added_by', $this->user->id)
                        ->orWhere(function ($q) {
                            $q->where(['to' => 'employee', 'department_id' => null])
                                ->orWhere(['department_id' => $departmentId]);
                        })
                        ->limit(10)
                        ->get();
                });
            }
            elseif ($this->viewNoticePermission == 'all') {
                $this->notices = Cache::remember('employee_dashboard_notices_all_' . $companyId, $dashboardCacheTtl, fn() => Notice::latest()
                    ->select('id', 'heading', 'created_at')
                    ->limit(10)
                    ->get());
            }
        }

        $this->tickets = Cache::remember('employee_dashboard_tickets_' . $companyId . '_' . $this->user->id, $dashboardCacheTtl, function () {
            return Ticket::where(function ($query) {
                $query->where('status', '=', 'open')
                    ->orWhere('status', '=', 'pending');
            })
                ->where(function ($query) {
                    $query->where('user_id', user()->id)
                        ->orWhere('agent_id', user()->id);
                })
                ->select('id', 'ticket_number', 'subject', 'status', 'updated_at', 'user_id', 'agent_id')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
        });

        $checkTicketAgent = Cache::remember('employee_dashboard_is_ticket_agent_' . $companyId . '_' . $this->user->id, now()->addMinutes(1), fn() => TicketAgentGroups::where('agent_id', user()->id)->exists());

        if ($checkTicketAgent) {
            $this->totalOpenTickets = Cache::remember('employee_dashboard_open_tickets_' . $companyId . '_' . $this->user->id, $dashboardCacheTtl, function () {
                return Ticket::whereHas('agent', function ($q) {
                    $q->where('id', user()->id);
                })->where('status', 'open')->count();
            });
        }

        $this->pendingTasks = Cache::remember('employee_dashboard_pending_tasks_' . $companyId . '_' . $this->user->id . '_' . $today, $dashboardCacheTtl, function () use ($completedTaskColumn) {
            return Task::query()
                ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
                ->where('task_users.user_id', $this->user->id)
                ->where('tasks.board_column_id', '<>', $completedTaskColumn->id)
                ->with([
                    'boardColumn:id,column_name,label_color',
                    'labels:id,label_name,color',
                ])
                ->select('tasks.id', 'tasks.task_short_code', 'tasks.heading', 'tasks.board_column_id', 'tasks.due_date')
                ->distinct()
                ->orderByDesc('tasks.id')
                ->limit(20)
                ->get();
        });

        // Getting Current Clock-in if exist
        $todayDate = now()->format('Y-m-d');
        $this->currentClockIn = Cache::remember('employee_dashboard_current_clock_in_' . $companyId . '_' . $this->user->id . '_' . $todayDate, $dashboardCacheTtl, function () use ($todayDate) {
            return Attendance::whereBetween('clock_in_time', [$todayDate . ' 00:00:00', $todayDate . ' 23:59:59'])
                ->select('id', 'clock_in_time', 'clock_out_time')
                ->where('user_id', $this->user->id)
                ->whereNull('clock_out_time')
                ->first();
        });

        $currentDate = now(company()->timezone)->format('Y-m-d');

        $this->checkTodayLeave = Cache::remember('employee_dashboard_today_leave_' . $companyId . '_' . $this->user->id . '_' . $todayDate, $dashboardCacheTtl, function () {
            return Leave::where('status', 'approved')
                ->select('id')
                ->where('leave_date', now(company()->timezone)->toDateString())
                ->where('user_id', user()->id)
                ->where('duration', '<>', 'half day')
                ->first();
        });

        // Check Holiday by date
        $this->checkTodayHoliday = Cache::remember('employee_dashboard_today_holiday_' . $companyId . '_' . $this->user->id . '_' . $todayDate, $dashboardCacheTtl, function () use ($currentDate, $user) {
            return Holiday::where('date', $currentDate)
                ->where(function ($query) use ($user) {
                    $query->orWhere('department_id_json', 'like', '%"' . ($user->employeeDetail?->department_id ?? $user->employeeDetails?->department_id ?? 0) . '"%')
                        ->orWhereNull('department_id_json');
                })
                ->where(function ($query) use ($user) {
                    $query->orWhere('designation_id_json', 'like', '%"' . ($user->employeeDetail?->designation_id ?? $user->employeeDetails?->designation_id ?? 0) . '"%')
                        ->orWhereNull('designation_id_json');
                })
                ->where(function ($query) use ($user) {
                    $query->orWhere('employment_type_json', 'like', '%"' . ($user->employeeDetail?->employment_type ?? $user->employeeDetails?->employment_type ?? '') . '"%')
                        ->orWhereNull('employment_type_json');
                })
                ->first();
        });

        $this->myActiveTimer = Cache::remember('employee_dashboard_active_timer_' . $companyId . '_' . $this->user->id, now()->addSeconds(20), function () {
            return ProjectTimeLog::with([
                'task:id,heading',
                'project:id,client_id',
                'breaks:id,project_time_log_id,start_time,end_time,total_minutes',
                'activeBreak:id,project_time_log_id,start_time,end_time',
            ])
                ->select('id', 'user_id', 'task_id', 'project_id', 'start_time', 'end_time', 'added_by')
                ->where('user_id', user()->id)
                ->whereNull('end_time')
                ->first();
        });

        $currentDay = now(company()->timezone)->format('m-d');

        $this->upcomingBirthdays = Cache::remember('employee_dashboard_birthdays_' . $companyId . '_' . $today, $dashboardCacheTtl, function () use ($currentDay, $dashboardUserCardRelations) {
            return EmployeeDetails::whereHas('user', function ($query) {
                return $query->where('status', 'active');
            })
                ->with(['user' => $dashboardUserCardRelations])
                ->select('employee_details.id', 'employee_details.user_id', 'employee_details.date_of_birth', DB::raw('MONTH(date_of_birth) months'), DB::raw('DAY(date_of_birth) as day'))
                ->whereNotNull('date_of_birth')
                ->where(function ($query) use ($currentDay) {
                    $query->whereRaw('DATE_FORMAT(`date_of_birth`, "%m-%d") >= "' . $currentDay . '"')->orderBy('date_of_birth');
                })
                ->limit(5)
                ->orderBy('months')
                ->orderBy('day')
                ->get()->values()->all();
        });

        $this->leave = Cache::remember('employee_dashboard_leave_today_' . $companyId . '_' . $today, $dashboardCacheTtl, function () use ($dashboardUserCardRelations) {
            return Leave::with([
                'user' => $dashboardUserCardRelations,
                'type:id,type_name,color',
            ])
                ->where('status', 'approved')
                ->where('leave_date', today(company()->timezone)->toDateString())
                ->get();
        });

        $this->workFromHome = Cache::remember('employee_dashboard_wfh_' . $companyId . '_' . $today, $dashboardCacheTtl, function () use ($dashboardUserCardRelations) {
            return Attendance::with(['user' => $dashboardUserCardRelations])
                ->select('id', 'user_id')
                ->where('work_from_type', 'home')
                ->whereBetween('attendances.clock_in_time', [now()->toDateString() . ' 00:00:00', now()->toDateString() . ' 23:59:59'])
                ->groupBy('user_id')
                ->get();
        });

        $this->leadAgent = Cache::remember('employee_dashboard_lead_agent_' . $companyId . '_' . $this->user->id, now()->addMinutes(2), fn() => LeadAgent::where('user_id', $this->user->id)->first());

        $now = now(company()->timezone);
        $this->weekStartDate = $now->copy()->startOfWeek($showClockIn->week_start_from);
        $this->weekEndDate = $this->weekStartDate->copy()->addDays(7);
        $this->weekPeriod = CarbonPeriod::create($this->weekStartDate, $this->weekStartDate->copy()->addDays(6)); // Get All Dates from start to end date

        $weekStartKey = $this->weekStartDate->format('Y-m-d');
        $weekEndKey = $this->weekEndDate->format('Y-m-d');
        $this->employeeShifts = Cache::remember('employee_dashboard_week_shifts_' . $companyId . '_' . $this->user->id . '_' . $weekStartKey, $dashboardCacheTtl, function () use ($weekStartKey, $weekEndKey) {
            return EmployeeShiftSchedule::where('user_id', user()->id)
                ->whereBetween('date', [$weekStartKey, $weekEndKey])
                ->select(DB::raw('DATE_FORMAT(date, "%Y-%m-%d") as dates'), 'employee_shift_schedules.*')
                ->with('shift', 'requestChange')
                ->get();
        });

        $this->employeeShiftDates = $this->employeeShifts->pluck('dates')->toArray();

        $currentWeekDates = [];
        $weekShifts = [];

        $weekHolidays = Cache::remember('employee_dashboard_week_holidays_' . $companyId . '_' . $weekStartKey, $dashboardCacheTtl, function () use ($weekStartKey, $weekEndKey) {
            return Holiday::whereBetween('date', [$weekStartKey, $weekEndKey])
                ->select(DB::raw('DATE_FORMAT(`date`, "%Y-%m-%d") as hdate'), 'occassion')
                ->get();
        });

        $holidayDates = $weekHolidays->pluck('hdate')->toArray();

        $weekLeaves = Cache::remember('employee_dashboard_week_leaves_' . $companyId . '_' . $this->user->id . '_' . $weekStartKey, $dashboardCacheTtl, function () use ($weekStartKey, $weekEndKey) {
            return Leave::with('type')
                ->select(DB::raw('DATE_FORMAT(`leave_date`, "%Y-%m-%d") as ldate'), 'leaves.*')
                ->where('user_id', user()->id)
                ->whereBetween('leave_date', [$weekStartKey, $weekEndKey])
                ->where('status', 'approved')
                ->where('duration', '<>', 'half day')
                ->get();
        });

        $leaveDates = $weekLeaves->pluck('ldate')->toArray();
        $generalShift = Cache::remember('employee_dashboard_general_shift_' . $companyId, now()->addMinutes(5), fn() => Company::with(['attendanceSetting', 'attendanceSetting.shift'])->first());

        // phpcs:ignore
        for ($i = $this->weekStartDate->copy(); $i < $this->weekEndDate->copy(); $i->addDay()) {
            $date = Carbon::parse($i);
            array_push($currentWeekDates, $date);

            if (in_array($date->toDateString(), $holidayDates)) {

                $leave = [];

                foreach ($weekHolidays as $holiday) {
                    if ($holiday->hdate == $date->toDateString()) {
                        $leave = '<i class="fa fa-star text-warning"></i> ' . $holiday->occassion;
                    }
                }

                array_push($weekShifts, $leave);

            }
            elseif (in_array($date->toDateString(), $leaveDates)) {

                $leave = [];

                foreach ($weekLeaves as $leav) {
                    if ($leav->ldate == $date->toDateString()) {
                        $leave = __('app.onLeave') . ': <span class="badge badge-success" style="background-color:' . $leav->type->color . '">' . $leav->type->type_name . '</span>';
                    }
                }

                array_push($weekShifts, $leave);

            }
            elseif (in_array($date->toDateString(), $this->employeeShiftDates)) {
                $shiftSchedule = [];

                foreach ($this->employeeShifts as $shift) {
                    if ($shift->dates == $date->toDateString()) {
                        $shiftSchedule = $shift;
                    }
                }

                array_push($weekShifts, $shiftSchedule);

            }
            else {
                $defaultShift = ($generalShift && $generalShift->attendanceSetting && $generalShift->attendanceSetting->shift) ? '<span class="badge badge-primary" style="background-color:' . $generalShift->attendanceSetting->shift->color . '">' . $generalShift->attendanceSetting->shift->shift_name . '</span>' : '--';
                array_push($weekShifts, $defaultShift);
            }

        }

        $this->upcomingAnniversaries = Cache::remember('employee_dashboard_anniversaries_' . $companyId . '_' . $today, $dashboardCacheTtl, function () use ($currentDay, $dashboardUserCardRelations) {
            return EmployeeDetails::whereHas('user', function ($query) {
                return $query->where('status', 'active');
            })
                ->with(['user' => $dashboardUserCardRelations])
                ->select('employee_details.id', 'employee_details.user_id', 'employee_details.joining_date', DB::raw('MONTH(joining_date) months'), DB::raw('DAY(joining_date) as day'))
                ->whereNotNull('joining_date')
                ->where(function ($query) use ($currentDay) {
                    $query->whereRaw('DATE_FORMAT(`joining_date`, "%m-%d") = "' . $currentDay . '"')->orderBy('joining_date');
                })
                ->orderBy('months')
                ->orderBy('day')
                ->get()->values()->all();
        });

        $this->currentWeekDates = $currentWeekDates;
        $this->weekShifts = $weekShifts;
        $this->showClockIn = $showClockIn->show_clock_in_button;
        $this->event_filter = explode(',', $employeeDetail?->calendar_view ?? '');

        $this->dateWiseTimelogs = collect();
        $this->dateWiseTimelogBreak = collect();
        $this->weekWiseTimelogs = 0;
        $this->weekWiseTimelogBreak = 0;

        if (in_array('week_timelog', $this->activeWidgets)) {
            $this->dateWiseTimelogs = Cache::remember('employee_dashboard_day_timelogs_' . $companyId . '_' . $this->user->id . '_' . $today, $dashboardCacheTtl, fn() => ProjectTimeLog::dateWiseTimelogs(now()->toDateString(), user()->id));
            $this->dateWiseTimelogBreak = Cache::remember('employee_dashboard_day_timelog_breaks_' . $companyId . '_' . $this->user->id . '_' . $today, $dashboardCacheTtl, fn() => ProjectTimeLogBreak::dateWiseTimelogBreak(now()->toDateString(), user()->id));
            $this->weekWiseTimelogs = Cache::remember('employee_dashboard_week_timelogs_' . $companyId . '_' . $this->user->id . '_' . $this->weekStartDate->toDateString(), $dashboardCacheTtl, fn() => ProjectTimeLog::weekWiseTimelogs($this->weekStartDate->copy()->toDateString(), $this->weekEndDate->copy()->toDateString(), user()->id));
            $this->weekWiseTimelogBreak = Cache::remember('employee_dashboard_week_timelog_breaks_' . $companyId . '_' . $this->user->id . '_' . $this->weekStartDate->toDateString(), $dashboardCacheTtl, fn() => ProjectTimeLogBreak::weekWiseTimelogBreak($this->weekStartDate->toDateString(), $this->weekEndDate->toDateString(), user()->id));
        }

        $this->appreciations = collect();
        if (in_array('appreciation', $this->activeWidgets)) {
            $this->appreciations = Cache::remember('employee_dashboard_appreciations_' . $companyId, $dashboardCacheTtl, fn() => Appreciation::with(['award', 'award.awardIcon'])
                ->with(['awardTo' => $dashboardUserCardRelations])
                ->orderByDesc('award_date')
                ->latest()
                ->limit(5)
                ->get());
        }

        $this->noticePeriod = in_array('admin', user_roles()) ? collect() : null;
        $this->probations = collect();
        $this->internships = collect();
        $this->contracts = collect();
        $this->probation = null;
        $this->internship = null;
        $this->contract = null;

        $requiresEmploymentCards = in_array('notice_period_duration', $this->activeWidgets)
            || in_array('probation_date', $this->activeWidgets)
            || in_array('internship_date', $this->activeWidgets)
            || in_array('contract_date', $this->activeWidgets);

        if ($requiresEmploymentCards) {
            $currentDay = now(company()->timezone)->format('Y-m-d');
            $employmentRows = Cache::remember('employee_dashboard_employment_cards_' . $companyId . '_' . user()->id . '_' . $currentDay . '_' . (in_array('admin', user_roles()) ? 'admin' : 'self'), $dashboardCacheTtl, function () use ($currentDay, $dashboardUserCardRelations) {
                $query = EmployeeDetails::whereHas('user', function ($query) {
                    return $query->where('status', 'active');
                })->with(['user' => $dashboardUserCardRelations]);

                if (!in_array('admin', user_roles())) {
                    $query->where('user_id', user()->id);
                }

                return $query->where(function ($query) use ($currentDay) {
                    $query->where(function ($q) use ($currentDay) {
                        $q->whereNotNull('notice_period_end_date')
                            ->where('notice_period_end_date', '>=', $currentDay);
                    })->orWhere(function ($q) use ($currentDay) {
                        $q->whereNotNull('probation_end_date')
                            ->where('probation_end_date', '>=', $currentDay);
                    })->orWhere(function ($q) use ($currentDay) {
                        $q->whereNotNull('internship_end_date')
                            ->where('internship_end_date', '>=', $currentDay);
                    })->orWhere(function ($q) use ($currentDay) {
                        $q->whereNotNull('contract_end_date')
                            ->where('contract_end_date', '>=', $currentDay);
                    });
                })->get();
            });

            if (in_array('admin', user_roles())) {
                if (in_array('notice_period_duration', $this->activeWidgets)) {
                    $this->noticePeriod = $employmentRows->filter(fn($row) => !is_null($row->notice_period_end_date) && $row->notice_period_end_date->toDateString() >= $currentDay)->sortBy('notice_period_end_date')->values();
                }

                if (in_array('probation_date', $this->activeWidgets)) {
                    $this->probations = $employmentRows->filter(fn($row) => !is_null($row->probation_end_date) && $row->probation_end_date->toDateString() >= $currentDay)->sortBy('probation_end_date')->values();
                }

                if (in_array('internship_date', $this->activeWidgets)) {
                    $this->internships = $employmentRows->filter(fn($row) => !is_null($row->internship_end_date) && $row->internship_end_date->toDateString() >= $currentDay)->sortBy('internship_end_date')->values();
                }

                if (in_array('contract_date', $this->activeWidgets)) {
                    $this->contracts = $employmentRows->filter(fn($row) => !is_null($row->contract_end_date) && $row->contract_end_date->toDateString() >= $currentDay)->sortBy('contract_end_date')->values();
                }
            }
            else {
                if (in_array('notice_period_duration', $this->activeWidgets)) {
                    $this->noticePeriod = $employmentRows->first(fn($row) => !is_null($row->notice_period_end_date) && $row->notice_period_end_date->toDateString() >= $currentDay);
                }

                if (in_array('probation_date', $this->activeWidgets)) {
                    $this->probation = $employmentRows->first(fn($row) => !is_null($row->probation_end_date) && $row->probation_end_date->toDateString() >= $currentDay);
                }

                if (in_array('internship_date', $this->activeWidgets)) {
                    $this->internship = $employmentRows->first(fn($row) => !is_null($row->internship_end_date) && $row->internship_end_date->toDateString() >= $currentDay);
                }

                if (in_array('contract_date', $this->activeWidgets)) {
                    $this->contract = $employmentRows->first(fn($row) => !is_null($row->contract_end_date) && $row->contract_end_date->toDateString() >= $currentDay);
                }
            }
        }

        return view('dashboard.employee.index', $this->data);
    }

    public function clockInModal()
    {
        $showClockIn = AttendanceSetting::first();

        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
        $endTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;
        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);
        $officeStartTime = $officeStartTime->setTimezone('UTC');
        $officeEndTime = $officeEndTime->setTimezone('UTC');

        if ($officeStartTime->gt($officeEndTime)) {
            $officeEndTime->addDay();
        }

        $this->cannotLogin = false;

        if ($showClockIn->employee_clock_in_out == 'yes') {

            if (is_null($this->attendanceSettings->early_clock_in) && !now()->between($officeStartTime, $officeEndTime) && $showClockIn->show_clock_in_button == 'no') {
                $this->cannotLogin = true;
            }
            else {
                $earlyClockIn = now(company()->timezone)->addMinutes($this->attendanceSettings->early_clock_in)->setTimezone('UTC');

                if (!$earlyClockIn->gte($officeStartTime) && $showClockIn->show_clock_in_button == 'no') {
                    $this->cannotLogin = true;
                }
            }

            if (now()->betweenIncluded($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay())) {
                $this->cannotLogin = false;
            }
        }
        else {
            $this->cannotLogin = true;
        }


        $this->shiftAssigned = $this->attendanceSettings;

        $this->attendanceSettings = attendance_setting();
        $this->location = Cache::remember('company_addresses_' . company()->id, now()->addMinutes(30), fn() => CompanyAddress::all());

        return view('dashboard.employee.clock_in_modal', $this->data);
    }

    public function storeClockIn(ClockInRequest $request)
    {
        $now = now();

        $showClockIn = AttendanceSetting::first();

        $this->attendanceSettings = $this->attendanceShift($showClockIn);

        $startTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
        $endTimestamp = now()->format('Y-m-d') . ' ' . $this->attendanceSettings->office_end_time;
        $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $startTimestamp, $this->company->timezone);
        $officeEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $endTimestamp, $this->company->timezone);

        if ($showClockIn->show_clock_in_button == 'yes') {
            $officeEndTime = now();
        }

        $officeStartTime = $officeStartTime->setTimezone('UTC');
        $officeEndTime = $officeEndTime->setTimezone('UTC');

        // check if user has clocked in on time or not
        $lateChckdata = Attendance::whereBetween('clock_in_time', [$officeStartTime, $officeEndTime])
            ->where('user_id', $this->user->id)
            ->orderBy('clock_in_time', 'asc')
            ->first();

        $islate = 'yes';

        if ($lateChckdata && $lateChckdata->late === 'no') {
            // user has reached office on time ,so late check will be disabled now
            $islate = 'no';
        }

        if ($officeStartTime->gt($officeEndTime)) {
            $officeEndTime->addDay();
        }

        $this->cannotLogin = false;
        $clockInCount = Attendance::getTotalUserClockInWithTime($officeStartTime, $officeEndTime, $this->user->id);

        if ($showClockIn->employee_clock_in_out == 'yes') {
            if (is_null($this->attendanceSettings->early_clock_in) && !now()->between($officeStartTime, $officeEndTime) && $showClockIn->show_clock_in_button == 'no') {
                $this->cannotLogin = true;
            }
            else {
                $earlyClockIn = now(company()->timezone)->addMinutes($this->attendanceSettings->early_clock_in);
                $earlyClockIn = $earlyClockIn->setTimezone('UTC');

                if ($earlyClockIn->gte($officeStartTime) || $showClockIn->show_clock_in_button == 'yes') {
                    $this->cannotLogin = false;
                }
                else {
                    $this->cannotLogin = true;
                }
            }

            if ($this->cannotLogin && now()->betweenIncluded($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay())) {
                $this->cannotLogin = false;
                $clockInCount = Attendance::getTotalUserClockInWithTime($officeStartTime->copy()->subDay(), $officeEndTime->copy()->subDay(), $this->user->id);
            }
        }
        else {
            $this->cannotLogin = true;
        }

        abort_403($this->cannotLogin);

        // Check user by ip
        if (attendance_setting()->ip_check == 'yes') {
            $ips = (array)json_decode(attendance_setting()->ip_address);

            if (!in_array($request->ip(), $ips)) {
                return Reply::error(__('messages.notAnAuthorisedDevice'));
            }
        }

        $employeeDetail = $user->employeeDetail ?? $user->employeeDetails ?? null;
        $enforceGeofence = $this->shouldEnforceGeofence($employeeDetail);

        // Check user by location
        if ($enforceGeofence) {
            $checkRadius = $this->isWithinRadius($request, $employeeDetail);

            if (!$checkRadius) {
                return Reply::error(__('messages.notAnValidLocation'));
            }
        }

        // Check maximum attendance in a day
        if ($clockInCount < $this->attendanceSettings->clockin_in_day) {

            // Set TimeZone And Convert into timestamp
            $currentTimestamp = $now->setTimezone('UTC');
            $currentTimestamp = $currentTimestamp->timestamp;

            // Set TimeZone And Convert into timestamp in halfday time
            if ($this->attendanceSettings->halfday_mark_time) {
                $halfDayTimestamp = $now->format('Y-m-d') . ' ' . $this->attendanceSettings->halfday_mark_time;
                $halfDayTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $halfDayTimestamp, $this->company->timezone);
                $halfDayTimestamp = $halfDayTimestamp->setTimezone('UTC');
                $halfDayTimestamp = $halfDayTimestamp->timestamp;
            }


            $timestamp = $now->format('Y-m-d') . ' ' . $this->attendanceSettings->office_start_time;
            $officeStartTime = Carbon::createFromFormat('Y-m-d H:i:s', $timestamp, $this->company->timezone);
            $officeStartTime = $officeStartTime->setTimezone('UTC');

            $lateTime = $officeStartTime->addMinutes($this->attendanceSettings->late_mark_duration);

            $checkTodayAttendance = Attendance::where('user_id', $this->user->id)
                ->whereBetween('attendances.clock_in_time', [$now->format('Y-m-d') . ' 00:00:00', $now->format('Y-m-d') . ' 23:59:59'])
                ->first();

            $attendance = new Attendance();
            $attendance->user_id = $this->user->id;
            $attendance->clock_in_time = $now;
            $attendance->clock_in_ip = request()->ip();

            $attendance->working_from = $request->working_from;
            $attendance->location_id = $request->location;
            $attendance->work_from_type = $request->work_from_type;

            if ($now->gt($lateTime) && $islate === 'yes') {
                $attendance->late = 'yes';
            }

            $leave = Leave::where('leave_date', $attendance->clock_in_time->format('Y-m-d'))
                ->where('status', 'approved')
                ->where('user_id', $this->user->id)->first();

            if (isset($leave) && !is_null($leave->half_day_type)) {
                $attendance->half_day = 'yes';
            }
            else {
                $attendance->half_day = 'no';
            }


            // Check day's first record and half day time
            if (
                !is_null($this->attendanceSettings->halfday_mark_time)
                && is_null($checkTodayAttendance)
                && isset($halfDayTimestamp)
                && ($currentTimestamp > $halfDayTimestamp)
                && ($showClockIn->show_clock_in_button == 'no')
            ) {
                $attendance->half_day = 'yes';
            }

            $currentLatitude = $request->currentLatitude;
            $currentLongitude = $request->currentLongitude;

            if ($currentLatitude != '' && $currentLongitude != '') {
                $attendance->latitude = $currentLatitude;
                $attendance->longitude = $currentLongitude;
            }

            $attendance->employee_shift_id = $this->attendanceSettings->id;

            $attendance->shift_start_time = $attendance->clock_in_time->toDateString() . ' ' . $this->attendanceSettings->office_start_time;

            if (Carbon::parse($this->attendanceSettings->office_start_time)->gt(Carbon::parse($this->attendanceSettings->office_end_time))) {
                $attendance->shift_end_time = $attendance->clock_in_time->addDay()->toDateString() . ' ' . $this->attendanceSettings->office_end_time;

            }
            else {
                $attendance->shift_end_time = $attendance->clock_in_time->toDateString() . ' ' . $this->attendanceSettings->office_end_time;
            }

            $attendance->save();

            return Reply::successWithData(__('messages.attendanceSaveSuccess'), ['time' => $now->format('h:i A'), 'ip' => $attendance->clock_in_ip, 'working_from' => $attendance->working_from]);
        }

        return Reply::error(__('messages.maxClockin'));
    }

    public function updateClockIn(Request $request)
    {
        $now = now();
        $attendance = Attendance::findOrFail($request->id);
        $this->attendanceSettings = attendance_setting();

        if ($this->attendanceSettings->ip_check == 'yes') {
            $ips = (array)json_decode($this->attendanceSettings->ip_address);

            if (!in_array($request->ip(), $ips)) {
                return Reply::error(__('messages.notAnAuthorisedDevice'));
            }
        }

        $attendance->clock_out_time = $now;
        $attendance->clock_out_ip = request()->ip();
        $attendance->save();

        return Reply::success(__('messages.attendanceSaveSuccess'));
    }

    /**
     * Calculate distance between two geo coordinates using Haversine formula and then compare
     * it with $radius.
     *
     * If distance is less than the radius means two points are close enough hence return true.
     * Else return false.
     *
     * @param Request $request
     *
     * @return boolean
     */
    private function shouldEnforceGeofence(?EmployeeDetails $employeeDetail): bool
    {
        if (is_null($employeeDetail)) {
            return attendance_setting()->radius_check == 'yes';
        }

        if ($employeeDetail->employee_type === 'sales_staff') {
            return false;
        }

        if ($employeeDetail->employee_type === 'office_staff') {
            return true;
        }

        return attendance_setting()->radius_check == 'yes';
    }

    private function isWithinRadius($request, ?EmployeeDetails $employeeDetail = null)
    {
        $radius = attendance_setting()->radius;
        $currentLatitude = $request->currentLatitude;
        $currentLongitude = $request->currentLongitude;
        $targetLatitude = null;
        $targetLongitude = null;

        if (empty($currentLatitude) || empty($currentLongitude)) {
            return false;
        }

        if (
            !is_null($employeeDetail)
            && $employeeDetail->employee_type === 'office_staff'
            && !empty($employeeDetail->office_latitude)
            && !empty($employeeDetail->office_longitude)
        ) {
            $targetLatitude = (float) $employeeDetail->office_latitude;
            $targetLongitude = (float) $employeeDetail->office_longitude;
            $radius = !empty($employeeDetail->allowed_radius) ? (int) $employeeDetail->allowed_radius : $radius;
        }
        else {
            $location = CompanyAddress::find($request->location);

            if (is_null($location) || empty($location->latitude) || empty($location->longitude)) {
                return false;
            }

            $targetLatitude = (float) $location->latitude;
            $targetLongitude = (float) $location->longitude;
        }

        $latFrom = deg2rad($targetLatitude);
        $latTo = deg2rad($currentLatitude);

        $lonFrom = deg2rad($targetLongitude);
        $lonTo = deg2rad($currentLongitude);

        $theta = $lonFrom - $lonTo;

        $dist = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($theta);
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $distance = $dist * 60 * 1.1515 * 1609.344;

        return $distance <= $radius;
    }

    public function attendanceShift($defaultAttendanceSettings)
    {
        return Cache::remember('employee_attendance_shift_' . company()->id . '_' . user()->id . '_' . now(company()->timezone)->format('YmdHi'), now()->addSeconds(60), function () use ($defaultAttendanceSettings) {
            $checkPreviousDayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
                ->where('date', now(company()->timezone)->subDay()->toDateString())
                ->first();

            $checkTodayShift = EmployeeShiftSchedule::with('shift')->where('user_id', user()->id)
                ->where('date', now(company()->timezone)->toDateString())
                ->first();

            $backDayFromDefault = Carbon::parse(now(company()->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_start_time);

            $backDayToDefault = Carbon::parse(now(company()->timezone)->subDay()->format('Y-m-d') . ' ' . $defaultAttendanceSettings->office_end_time);

            if ($backDayFromDefault->gt($backDayToDefault)) {
                $backDayToDefault->addDay();
            }

            $nowTime = Carbon::createFromFormat('Y-m-d H:i:s', now(company()->timezone)->toDateTimeString(), 'UTC');

            if ($checkPreviousDayShift && $nowTime->betweenIncluded($checkPreviousDayShift->shift_start_time, $checkPreviousDayShift->shift_end_time)) {
                $attendanceSettings = $checkPreviousDayShift;

            }
            else if ($nowTime->betweenIncluded($backDayFromDefault, $backDayToDefault)) {
                $attendanceSettings = $defaultAttendanceSettings;

            }
            else if ($checkTodayShift &&
                ($nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time)
                    || $nowTime->gt($checkTodayShift->shift_end_time)
                    || (!$nowTime->betweenIncluded($checkTodayShift->shift_start_time, $checkTodayShift->shift_end_time) && $defaultAttendanceSettings->show_clock_in_button == 'no'))
            ) {
                $attendanceSettings = $checkTodayShift;
            }
            else if ($checkTodayShift && !is_null($checkTodayShift->shift->early_clock_in)) {
                $attendanceSettings = $checkTodayShift;
            }
            else {
                $attendanceSettings = $defaultAttendanceSettings;
            }

            return $attendanceSettings->shift;
        });

    }

}
