<?php

namespace App\Http\Controllers;

use App\Models\LeadFollowUp;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends AccountBaseController
{
    private const VIEWABLE_PERMISSION_TYPES = ['all', 'added', 'owned', 'both'];

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.calendar';
        $this->activeMenu = 'calendar';
        $this->middleware(function ($request, $next) {
            $this->authorizeCalendarAccess();

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
        [$rangeStart, $rangeEnd] = $this->calendarRange($request);

        $followups = LeadFollowUp::query()
            ->select([
                'id',
                'lead_id',
                'next_follow_up_date',
                'status',
                'remark',
                'latitude',
                'longitude',
            ])
            ->with(['lead:id,company_id,client_name'])
            ->whereNotNull('lead_id')
            // FullCalendar's end value is exclusive.
            ->where('next_follow_up_date', '>=', $rangeStart)
            ->where('next_follow_up_date', '<', $rangeEnd)
            ->whereHas('lead', fn ($leadQuery) => $leadQuery->accessibleTo($user))
            ->orderBy('next_follow_up_date')
            ->orderBy('id')
            ->get();

        $statusMeta = [
            'completed' => ['color' => '#16a34a', 'label' => __('app.completed')],
            'canceled' => ['color' => '#6b7280', 'label' => __('app.canceled')],
            'overdue' => ['color' => '#dc2626', 'label' => __('app.overdue')],
            'today' => ['color' => '#eab308', 'label' => __('app.today')],
            'upcoming' => ['color' => '#2563eb', 'label' => __('app.upcoming')],
        ];

        $company = company();
        $companyTimezone = $company->timezone;
        $dateFormat = $company->date_format;
        $timeFormat = $company->time_format;
        $leadUrlTemplate = route('lead-contact.show', ['lead_contact' => '__lead_id__']) . '?tab=follow-up';
        $now = now($companyTimezone);
        $today = $now->copy()->startOfDay();

        $events = [];

        foreach ($followups as $followup) {
            if (!$followup->lead) {
                continue;
            }

            $followUpAt = $followup->next_follow_up_date?->timezone($companyTimezone);
            $followUpDay = $followUpAt?->copy()->startOfDay();
            $status = strtolower((string) ($followup->status ?: 'pending'));

            if (!isset($statusMeta[$status])) {
                $status = $followUpAt && $followUpAt->lt($now)
                    ? 'overdue'
                    : ($followUpDay && $followUpDay->equalTo($today) ? 'today' : 'upcoming');
            }

            $color = $statusMeta[$status]['color'];
            $statusLabel = $statusMeta[$status]['label'];

            $events[] = [
                'id' => 'fup-' . $followup->id,
                'title' => $followup->lead->client_name,
                'start' => $followUpAt?->toIso8601String(),
                'color' => $color,
                'allDay' => false,
                'extendedProps' => [
                    'type' => 'followup',
                    'lead_id' => $followup->lead_id,
                    'followup_id' => $followup->id,
                    'status' => $status,
                    'status_label' => $statusLabel,
                    'followup_date' => $followUpAt?->format($dateFormat),
                    'reminder_time' => $followUpAt?->format($timeFormat),
                    'note' => trim(strip_tags((string) $followup->remark)) ?: '--',
                    'latitude' => $followup->latitude,
                    'longitude' => $followup->longitude,
                    'maps_url' => ($followup->latitude && $followup->longitude)
                        ? 'https://www.google.com/maps/search/?api=1&query=' . $followup->latitude . ',' . $followup->longitude
                        : null,
                    'redirect_url' => str_replace('__lead_id__', (string) $followup->lead_id, $leadUrlTemplate),
                ],
            ];
        }

        return response()->json($events);
    }

    private function authorizeCalendarAccess(): void
    {
        $user = auth()->user();

        abort_403(!$user || !in_array('leads', $user->modules));

        // Admins already receive the complete company lead calendar. Other
        // users need both permissions because events contain lead data and
        // follow-up notes.
        if (!$user->hasRole('admin')) {
            abort_403(!in_array($user->permission('view_lead'), self::VIEWABLE_PERMISSION_TYPES, true));
            abort_403(!in_array($user->permission('view_lead_follow_up'), self::VIEWABLE_PERMISSION_TYPES, true));
        }
    }

    /**
     * Convert FullCalendar's company-local range to the UTC range stored in
     * the database. FullCalendar sends an exclusive end boundary.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function calendarRange(Request $request): array
    {
        $start = $request->input('start');
        $end = $request->input('end');

        abort_unless($start && $end, 422, 'Calendar range is required.');

        try {
            $rangeStart = Carbon::parse($start, company()->timezone)->setTimezone('UTC');
            $rangeEnd = Carbon::parse($end, company()->timezone)->setTimezone('UTC');
        }
        catch (\Throwable $exception) {
            abort(422, 'Invalid calendar range.');
        }

        abort_unless($rangeEnd->greaterThan($rangeStart), 422, 'Invalid calendar range.');

        return [$rangeStart, $rangeEnd];
    }
}
