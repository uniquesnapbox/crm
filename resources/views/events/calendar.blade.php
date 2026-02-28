@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/full-calendar/main.min.css') }}">
    <style>
        #calendar { max-width: 100%; margin: 0 auto; }
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
                url: '/calendar/events',
            },
            eventClick: function(arg) {
                // you can handle clicks differently based on type
                if (arg.event.extendedProps.type == 'task') {
                    var url = "{{ route('tasks.show', ':id') }}";
                    url = url.replace(':id', arg.event.id);
                    window.location.href = url;
                } else if (arg.event.extendedProps.type == 'followup') {
                    // maybe open lead followup detail if route exists
                }
            }
        });

        calendar.render();

    </script>
@endpush
