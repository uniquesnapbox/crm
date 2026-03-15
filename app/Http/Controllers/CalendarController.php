<?php

namespace App\Http\Controllers;

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
            abort_403(!in_array('leads', $this->user->modules));

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
     * Return lead follow-ups in FullCalendar format.
     */
    public function events(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $followups = LeadFollowUp::with('lead')
                ->whereNotNull('lead_id')
                ->whereNotNull('next_follow_up_date')
                ->get();
        }
        else {
            $followups = LeadFollowUp::with('lead')
                ->where('added_by', $user->id)
                ->whereNotNull('lead_id')
                ->whereNotNull('next_follow_up_date')
                ->get();
        }

        $events = [];

        foreach ($followups as $followup) {
            if (!$followup->lead) {
                continue;
            }

            $followUpAt = $followup->next_follow_up_date?->timezone(company()->timezone);

            $events[] = [
                'id' => 'fup-' . $followup->id,
                'title' => $followup->lead->client_name,
                'start' => $followUpAt?->toIso8601String(),
                'color' => '#F97316',
                'allDay' => false,
                'extendedProps' => [
                    'type' => 'followup',
                    'lead_id' => $followup->lead_id,
                    'followup_id' => $followup->id,
                    'followup_date' => $followUpAt?->format(company()->date_format),
                    'reminder_time' => $followUpAt?->format(company()->time_format),
                    'latitude' => $followup->latitude,
                    'longitude' => $followup->longitude,
                    'redirect_url' => route('lead-contact.show', [$followup->lead_id]) . '?tab=follow-up',
                ],
            ];
        }

        return response()->json($events);
    }
}
