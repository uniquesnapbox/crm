<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\LeadFollowUp;
use Illuminate\Http\Request;

class CalendarController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.calendar';
        $this->activeMenu = 'calendar';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('tasks', $this->user->modules) && !in_array('leads', $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display unified calendar view.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('events.calendar', $this->data);
    }

    /**
     * Return combined tasks and followups in FullCalendar format.
     */
    public function events(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $tasks = Task::whereNotNull('due_date')->get();
            $followups = LeadFollowUp::with('lead')->whereNotNull('next_follow_up_date')->get();
        } else {
            $tasks = Task::where('assigned_to', $user->id)->get();
            $followups = LeadFollowUp::with('lead')->where('added_by', $user->id)->get();
        }

        $events = [];

        foreach ($tasks as $task) {
            $events[] = [
                'id'   => $task->id,
                'title' => 'Task: ' . $task->heading,
                'start' => $task->due_date ? $task->due_date->format('Y-m-d') : $task->start_date->format('Y-m-d'),
                'color' => '#0B0B7A',
                'type' => 'task'
            ];
        }

        foreach ($followups as $followup) {
            $events[] = [
                'id'   => 'fup-' . $followup->id,
                'title' => 'Follow-up: ' . optional($followup->lead)->client_name,
                'start' => $followup->next_follow_up_date ? $followup->next_follow_up_date->format('Y-m-d') : null,
                'color' => '#F97316',
                'type' => 'followup'
            ];
        }

        return response()->json($events);
    }
}
