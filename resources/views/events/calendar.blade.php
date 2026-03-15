@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/full-calendar/main.min.css') }}">
    <style>
        #calendar { max-width: 100%; margin: 0 auto; }
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
        <div class="d-grid d-lg-flex d-md-flex action-bar my-3">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <!-- optional action buttons -->
            </div>
        </div>

        <x-cards.data>
            <div id="calendar"></div>
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
                ? `<div><span class="label">Location:</span><a href="${props.maps_url}" target="_blank" rel="noopener">Open in Google Maps</a></div>`
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
            events: {
                url: "{{ route('crm.calendar.events') }}",
            },
            eventDidMount: function(info) {
                if (info.event.extendedProps.type === 'followup') {
                    $(info.el).attr('data-toggle', 'tooltip');
                    $(info.el).attr('data-html', 'true');
                    $(info.el).attr('data-placement', 'top');
                    $(info.el).attr('title', eventDetailsHtml(info.event));
                    $(info.el).tooltip({
                        container: 'body',
                        html: true,
                        trigger: 'hover'
                    });
                }
            },
            eventWillUnmount: function(info) {
                $(info.el).tooltip('dispose');
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

        calendar.render();

    </script>
@endpush
