@php
    $emptyValue = html_entity_decode('&#8212;');
    $formatActivityDate = function ($date) use ($emptyValue) {
        return $date
            ? Illuminate\Support\Carbon::parse($date)->timezone(company()->timezone)->format('d M')
            : $emptyValue;
    };
@endphp

@foreach ($rows as $row)
    <tr>
        <td>{{ $row['name'] ?: $emptyValue }}</td>
        <td>{{ $row['number'] ?: $emptyValue }}</td>
        <td>{{ $row['category'] ?: $emptyValue }}</td>
        <td>
            <div class="employee-lead-activity-pair">
                <span title="Current">{{ $row['status']['current'] ?: $emptyValue }}</span>
                <span title="Previous">{{ $row['status']['previous'] ?: $emptyValue }}</span>
            </div>
        </td>
        <td>
            <div class="employee-lead-activity-pair">
                <span title="Current">{{ $row['interest_level']['current'] ?: $emptyValue }}</span>
                <span title="Previous">{{ $row['interest_level']['previous'] ?: $emptyValue }}</span>
            </div>
        </td>
        <td>
            <div class="employee-lead-activity-pair">
                <span title="Current">{{ $formatActivityDate($row['followup_date']['current']) }}</span>
                <span title="Previous">{{ $formatActivityDate($row['followup_date']['previous']) }}</span>
            </div>
        </td>
    </tr>
@endforeach
