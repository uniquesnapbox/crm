<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Attendace Report</title>
    <meta name="author" content="Andrés Herrera García">
    <meta name="description" content="PDF de una fotomulta">
    <meta name="keywords" content="fotomulta, comparendo">
    <style>
        table.header {
            width: 100%;
            padding: 0px;
            margin: 0px;
            font-family: 'noto-sans, DejaVu Sans , sans-serif';
            font-size: 12px;
            line-height: 1.4;
            color: #28313c;
            margin-bottom: 20px;
        }

        table.content {
            width: 100%;
            border-spacing: 0;
            padding: 0px;
            margin: 0px;
            font-family: 'noto-sans, DejaVu Sans , sans-serif';
            font-size: 10px;
            line-height: 1.4;
            color: #28313c;
            margin-bottom: 20px;
        }

        .content th, .content td {
            border: 1px solid #cccccc;
            padding: 1px 3px;
            text-align: center;
        }

        .content .row {
            border: 1px solid #DBDBDB;
        }

        #logo {
            height: 50px;
        }

        .activity-section-title {
            font-family: 'noto-sans, DejaVu Sans , sans-serif';
            font-size: 12px;
            font-weight: bold;
            color: #28313c;
            margin: 8px 0 4px;
        }

        .activity-note {
            font-family: 'noto-sans, DejaVu Sans , sans-serif';
            font-size: 9px;
            color: #666666;
            margin: 0 0 6px;
        }

        .activity-table th, .activity-table td {
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }

        .activity-table .activity-date,
        .activity-table .activity-time,
        .activity-table .activity-status {
            white-space: nowrap;
        }

    </style>
</head>

<body>
<table class="header">
    <tr>
        <td><img src="{{ $company->logo_url }}" alt="{{ $company->company_name }}"
                 id="logo"/></td>
        <td align="right">{{ \Carbon\Carbon::parse('01-' . $month . '-' . $year)->translatedFormat('F-Y') }} @lang('app.menu.attendanceReport')</td>
    </tr>
</table>

<table class="content">
    <thead>
    <tr>
        <th style="vertical-align: middle; text-align: left; max-width: 150px;">@lang('app.employee')</th>
        @for ($i = 1; $i <= $daysInMonth; $i++)
            <th>{{ $i }}
                <br> {{ $weekMap[\Carbon\Carbon::parse(\Carbon\Carbon::parse($i . '-' . $month . '-' . $year))->dayOfWeek] }}
            </th>
        @endfor
        <th>@lang('app.total')</th>
    </tr>
    </thead>

    <tbody>
    @php
        $totalAbsent = 0;
        $totalLeaves = 0;
        $totalHalfDay = 0;
        $totalHoliday = 0;
        $allPresent = 0;
    @endphp
    @foreach ($employeeAttendence as $key => $attendance)
        @php
            $totalPresent = 0;
            $userId = explode('#', $key);
            $userId = $userId[0];
        @endphp
        <tr>
            <td style="text-align: left;"> {!! end($attendance) !!} </td>
            @foreach ($attendance as $key2 => $day)
                @if ($key2 + 1 <= count($attendance))
                    @php
                        $attendanceDate = \Carbon\Carbon::parse($year.'-'.$month.'-'.$key2);
                    @endphp
                    <td>
                        @if ($day == 'Leave')
                            L
                            @php
                                $totalLeaves = $totalLeaves + 1;
                            @endphp
                        @elseif ($day == 'Half Day')
                            HD
                            @php
                                $totalHalfDay = $totalHalfDay + 1;
                            @endphp
                        @elseif ($day == 'Absent')
                            <span style="color: #c50909">&times;</span>
                            @php
                                $totalAbsent = $totalAbsent + 1;
                            @endphp
                        @elseif ($day == 'Holiday')
                            <span style="color: #FCBD01">&bigstar;</span>
                            @php
                                $totalHoliday = $totalHoliday + 1;
                            @endphp
                        @else
                            @if ($day != '-')
                                @php
                                    $totalPresent = $totalPresent + 1;
                                    $allPresent = $allPresent + 1;
                                @endphp
                            @endif

                            <span style="color: green">{!! $day !!}</span>
                        @endif
                    </td>
                @endif
            @endforeach
            <td>{!! $totalPresent . ' / ' . (count($attendance) - 1) !!}</td>
        </tr>
    @endforeach
    </tbody>
</table>


@if (isset($documentedWorkActivities) && $documentedWorkActivities->isNotEmpty())
    <div class="activity-section-title">Documented Work Activity</div>
    <div class="activity-note">The timestamps below are documented work activity times, not exact attendance IN/OUT times. Normal office timing: 10:00 AM – 6:00 PM.</div>
    <table class="content activity-table">
        <thead>
        <tr>
            <th>Employee</th>
            <th>Date</th>
            <th>Time</th>
            <th>Task/Activity</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($documentedWorkActivities as $workActivity)
            <tr>
                <td>{{ $workActivity->user?->name ?? 'Unknown employee' }}</td>
                <td class="activity-date">{{ $workActivity->activity_date->format('d-M-Y') }}</td>
                <td class="activity-time">
                    @if ($workActivity->start_time && $workActivity->end_time)
                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $workActivity->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::createFromFormat('H:i:s', $workActivity->end_time)->format('h:i A') }}
                    @elseif ($workActivity->start_time)
                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $workActivity->start_time)->format('h:i A') }}
                    @else
                        —
                    @endif
                </td>
                <td>{{ $workActivity->activity }}</td>
                <td class="activity-status">{{ $workActivity->status }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif


<table class="content">
    <tr>
        <td><span style="color: green">&check;</span> &rightarrow; @lang('app.present')</td>
        <td><span style="color: #c50909">&times;</span> &rightarrow; @lang('app.absent')</td>
        <td><span style="color: #FCBD01">&bigstar;</span> &rightarrow; @lang('app.menu.holiday')</td>
    </tr>
    <tr>
        <td>@lang('app.totalDays'): {{ $daysInMonth }}</td>
        <td>@lang('modules.attendance.daysPresent'): {{ $allPresent }}</td>
        <td>@lang('app.totalAbsent'): {{ $totalAbsent }}</td>
    </tr>
    <tr>
        <td>@lang('app.totalLeave') : {{ $totalLeaves }}</td>
        <td>@lang('app.totalHalfDayLeave') : {{ $totalHalfDay }}</td>
        <td></td>
    </tr>
</table>

</body>
