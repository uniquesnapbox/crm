<?php

namespace App\Traits;

use App\Models\DashboardWidget;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectStatusSetting;
use App\Models\ProjectTimeLog;
use App\Models\ProjectTimeLogBreak;
use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 *
 */
trait ProjectDashboard
{

    /**
     *
     * @return void
     */
    public function projectDashboard()
    {
        abort_403($this->viewProjectDashboard !== 'all');

        $this->pageTitle = 'app.projectDashboard';

        $this->startDate = (request('startDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('startDate')) : now($this->company->timezone)->startOfMonth();

        $this->endDate = (request('endDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('endDate')) : now($this->company->timezone);

        $startDate = $this->startDate->toDateString();
        $endDate = $this->endDate->toDateString();

        $this->totalProject = Project::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();

        $hoursLogged = ProjectTimeLog::whereDate('start_time', '>=', $startDate)
            ->whereDate('end_time', '<=', $endDate)
            ->whereNotNull('project_id')
            ->where('approved', 1)
            ->sum('total_minutes');

        $breakMinutes = ProjectTimeLogBreak::join('project_time_logs', 'project_time_log_breaks.project_time_log_id', '=', 'project_time_logs.id')
            ->whereDate('project_time_logs.start_time', '>=', $startDate)
            ->whereDate('project_time_logs.end_time', '<=', $endDate)
            ->whereNotNull('project_time_logs.project_id')
            ->sum('project_time_log_breaks.total_minutes');

        $hoursLogged = $hoursLogged - $breakMinutes;

        /** @phpstan-ignore-next-line */
        $timeLog = CarbonInterval::formatHuman($hoursLogged);

        $this->totalHoursLogged = $timeLog;

        $this->totalOverdueProject = Project::whereNotNull('deadline')
            ->whereBetween('deadline', [$startDate, $endDate])
            ->count();

        $this->widgets = DashboardWidget::where('dashboard_type', 'admin-project-dashboard')->get();
        $this->activeWidgets = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $this->pendingMilestone = ProjectMilestone::whereBetween('project_milestones.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with('project', 'currency')
            ->whereHas('project')
            ->where('status', 'incomplete')
            ->get();

        $this->statusWiseProject = $this->statusChartData($startDate, $endDate);

        $this->view = 'dashboard.ajax.project';
    }

    public function statusChartData($startDate, $endDate)
    {
        $labels = ProjectStatusSetting::where('status', 'active')->pluck('status_name');
        $data['labels'] = ProjectStatusSetting::where('status', 'active')->pluck('status_name');
        $data['colors'] = ProjectStatusSetting::where('status', 'active')->pluck('color');
        $data['values'] = [];

        foreach ($labels as $label) {
            $data['values'][] = Project::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->where('status', $label)->count();
        }

        return $data;
    }

}
