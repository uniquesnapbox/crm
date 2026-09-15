@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/full-calendar/main.min.css') }}">
    <style>
        #calendar { max-width: 100%; margin: 0 auto; }
        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-bottom: 16px;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
        }
        .calendar-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #4b5563;
        }
        .calendar-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
        }
        .calendar-event-content {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 100%;
        }
        .calendar-event-status {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            flex: 0 0 auto;
            font-size: 11px;
            font-weight: 600;
        }
        .calendar-event-status i { font-size: 10px; }
        .calendar-event-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .calendar-status-completed { color: #15803d; }
        .calendar-status-canceled { color: #4b5563; }
        .calendar-status-overdue { color: #b91c1c; }
        .calendar-status-today { color: #a16207; }
        .calendar-status-upcoming { color: #1d4ed8; }
        .calendar-empty-state {
            padding: 36px 16px;
            text-align: center;
            color: #6b7280;
        }
        .follow-up-tooltip {
            text-align: left;
            max-width: 320px;
        }
        .follow-up-tooltip .label {
            font-weight: 600;
            display: inline-block;
            min-width: 92px;
        }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <x-cards.data :title="__('app.menu.calendar')">
            <div class="calendar-legend">
                <div class="calendar-legend-item">
                    <span class="calendar-legend-dot" style="background:#16a34a;"></span>
                    <span>Completed</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-dot" style="background:#dc2626;"></span>
                    <span>Overdue (not completed)</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-dot" style="background:#6b7280;"></span>
                    <span>Canceled</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-dot" style="background:#eab308;"></span>
                    <span>Today (pending)</span>
                </div>
                <div class="calendar-legend-item">
                    <span class="calendar-legend-dot" style="background:#2563eb;"></span>
                    <span>Upcoming</span>
                </div>
            </div>
            <div id="calendar" aria-label="{{ __('app.menu.calendar') }}"></div>
            <div id="calendar-empty-state" class="calendar-empty-state d-none" role="status">
                No follow-ups are scheduled for this date range.
            </div>
            <div id="calendar-status" class="sr-only" role="status" aria-live="polite"></div>
        </x-cards.data>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/full-calendar/main.min.js') }}"></script>
    <script src="{{ asset('vendor/full-calendar/locales-all.min.js') }}"></script>

    <script>
        var initialLocaleCode = '{{ user()->locale }}';
        var calendarEl = document.getElementById('calendar');

        const escapeHtml = (value) => {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const eventDetailsHtml = (event) => {
            const props = event.extendedProps || {};
            const mapsLink = props.maps_url
                ? `<div><span class="label">Location:</span><a href="${escapeHtml(props.maps_url)}" target="_blank" rel="noopener">Open in Google Maps</a></div>`
                : '';

            return `
                <div class="follow-up-tooltip">
                    <div><span class="label">Lead:</span>${escapeHtml(event.title)}</div>
                    <div><span class="label">Reminder:</span>${escapeHtml(props.reminder_time || '--')}</div>
                    <div><span class="label">Note:</span>${escapeHtml(props.note || '--')}</div>
                    ${mapsLink}
                </div>
            `;
        };

        const statusIcons = {
            completed: 'fa-check-circle',
            canceled: 'fa-ban',
            overdue: 'fa-exclamation-circle',
            today: 'fa-clock',
            upcoming: 'fa-calendar-day'
        };

        const emptyStateEl = document.getElementById('calendar-empty-state');
        const statusEl = document.getElementById('calendar-status');

        const setCalendarStatus = (message) => {
            if (statusEl) {
                statusEl.textContent = message;
            }
        };

        const setCalendarEmptyState = (isEmpty) => {
            if (emptyStateEl) {
                emptyStateEl.classList.toggle('d-none', !isEmpty);
            }
        };

        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: initialLocaleCode,
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            navLinks: true,
            selectable: false,
            editable: false,
            dayMaxEvents: true,
            lazyFetching: true,
            progressiveEventRendering: true,
            eventContent: function(arg) {
                const props = arg.event.extendedProps || {};
                const status = Object.prototype.hasOwnProperty.call(statusIcons, props.status)
                    ? props.status
                    : 'upcoming';
                const label = escapeHtml(props.status_label || status);

                return {
                    html: `<span class="calendar-event-content">
                        <span class="calendar-event-status calendar-status-${status}" aria-label="${label}">
                            <i class="fa ${statusIcons[status]}" aria-hidden="true"></i><span>${label}</span>
                        </span>
                        <span class="calendar-event-title">${escapeHtml(arg.event.title)}</span>
                    </span>`
                };
            },
            events: {
                url: "{{ route('crm.calendar.events') }}",
            },
            loading: function(isLoading) {
                if (isLoading) {
                    setCalendarStatus('Loading follow-ups.');
                }
            },
            eventsSet: function(events) {
                setCalendarEmptyState(events.length === 0);
                setCalendarStatus(events.length === 0
                    ? 'No follow-ups are scheduled for this date range.'
                    : `${events.length} follow-up${events.length === 1 ? '' : 's'} loaded.`);
            },
            eventSourceFailure: function() {
                setCalendarEmptyState(false);
                setCalendarStatus('Unable to load follow-ups. Please try again.');
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.type === 'followup') {
                    $(info.el).attr('data-calendar-event-id', info.event.id);
                }
            },
            eventClick: function(arg) {
                if (arg.event.extendedProps.type === 'followup' && arg.event.extendedProps.redirect_url) {
                    arg.jsEvent.preventDefault();

                    Swal.fire({
                        title: escapeHtml(arg.event.title),
                        html: eventDetailsHtml(arg.event),
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Open Follow-up',
                        cancelButtonText: 'Close',
                        customClass: {
                            confirmButton: 'btn btn-primary mr-3',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = arg.event.extendedProps.redirect_url;
                        }
                    });
                }
            }
        });

        $(calendarEl).tooltip({
            selector: '.fc-event',
            container: 'body',
            html: true,
            trigger: 'hover',
            title: function() {
                const event = calendar.getEventById($(this).attr('data-calendar-event-id'));

                return event ? eventDetailsHtml(event) : '';
            }
        });

        calendar.render();

    </script>
@endpush
