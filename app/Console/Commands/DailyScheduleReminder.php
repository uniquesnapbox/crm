<?php

namespace App\Console\Commands;

use App\Events\DailyScheduleEvent;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\LeadFollowUp;
use App\Models\TaskboardColumn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Recruit\Entities\RecruitInterviewEmployees;
use Modules\Recruit\Entities\RecruitInterviewSchedule;

class DailyScheduleReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily-schedule-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send the daily updates to employees about their tasks, leaves, holidays, events and interviews';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeIds = User::withRole('employee')->pluck('id')->toArray();
        $today = Carbon::now('Asia/Kolkata')->toDateString();
        $data = [];
        $completedTaskColumn = TaskboardColumn::completeColumn();

        foreach($employeeIds as $employeeId)
        {
            $user = User::with(['employeeDetail', 'tasks' => function($query) use($completedTaskColumn, $today) {
                    $query->whereDate('due_date', '=', $today)
                        ->where('board_column_id', '<>', $completedTaskColumn->id);
            }, 'leaves' => function($leaves) use ($today){
                $leaves->whereDate('leave_date', '=', $today)
                    ->where('leaves.status', 'approved');
            },
            ])->where('id', $employeeId)->first();

            $events = Event::with('attendee', 'attendee.user')
                ->where(function ($query) use($user) {
                    $query->whereHas('attendee', function ($query) use($user) {
                        $query->where('user_id', $user->id);
                    });
                    $query->orWhere('added_by', $user->id);
                })
            ->whereDate('start_date_time', '<=', $today)->whereDate('end_date_time', '>=', $today)->count();

            $holiday = Holiday::where(function ($query) use ($user) {
                $query->where('added_by', $user->id)
                    ->orWhere(function($query) use($user){
                        $query->where(function ($q) use ($user) {
                            $q->orWhere('department_id_json', 'like', '%"' . $user->employeeDetail->department_id . '"%')
                                ->orWhereNull('department_id_json');
                        });
                        $query->where(function ($q) use ($user) {
                            $q->orWhere('designation_id_json', 'like', '%"' . $user->employeeDetail->designation_id . '"%')
                                ->orWhereNull('designation_id_json');
                        });
                        $query->where(function ($q) use ($user) {
                            $q->orWhere('employment_type_json', 'like', '%"' . $user->employeeDetail->employment_type . '"%')
                                ->orWhereNull('employment_type_json');
                        });
                    });
            })->whereDate('date', '=', $today)->count();

            $interview = RecruitInterviewEmployees::with(['schedule' => function($q) use ($today){
                $q->whereDate('schedule_date', '=', $today);
            }])->where('user_id', $user->id)->count();

            $timezone = $user->company?->timezone ?: config('app.timezone');
            $dayStart = Carbon::now($timezone)->startOfDay()->utc();
            $dayEnd = Carbon::now($timezone)->endOfDay()->utc();
            $followUpQuery = LeadFollowUp::query()
                ->where('status', 'pending')
                ->whereBetween('next_follow_up_date', [$dayStart, $dayEnd])
                ->whereHas('lead', function ($query) use ($user) {
                    $query->where('company_id', $user->company_id)
                        ->where(function ($scope) use ($user) {
                            $scope->where('added_by', $user->id)
                                ->orWhere('assigned_to', $user->id);
                        });
                });

            $todayFollowUps = (clone $followUpQuery)->count();
            $meetings = (clone $followUpQuery)
                ->where(function ($query) {
                    $query->where('remark', 'like', '%meeting%')
                        ->orWhere('remark', 'like', '%meet%');
                })
                ->count();
            $demos = (clone $followUpQuery)
                ->where('remark', 'like', '%demo%')
                ->count();
            $pendingCalls = (clone $followUpQuery)
                ->where(function ($query) {
                    $query->where(function ($scope) {
                        $scope->whereNull('remark')
                            ->orWhere('remark', 'not like', '%meeting%');
                    })->where(function ($scope) {
                        $scope->whereNull('remark')
                            ->orWhere('remark', 'not like', '%meet%');
                    })->where(function ($scope) {
                        $scope->whereNull('remark')
                            ->orWhere('remark', 'not like', '%demo%');
                    });
                })
                ->count();

            $data['interview'][$user->id] = $interview;
            $data['user'][$user->id] = $user;
            $data['holidays'][$user->id] = $holiday;
            $data['leaves'][$user->id] = $user->leaves->count();
            $data['tasks'][$user->id] = $user->tasks->count();
            $data['events'][$user->id] = $events;
            $data['today_followups'][$user->id] = $todayFollowUps;
            $data['pending_calls'][$user->id] = $pendingCalls;
            $data['meetings'][$user->id] = $meetings;
            $data['demos'][$user->id] = $demos;
        }
        event(new DailyScheduleEvent($data['user'], $data));
    }

}
