@php
    $emptyValue = html_entity_decode('&#8212;');
    $formatActivityDate = function ($date) use ($emptyValue) {
        return $date
            ? Illuminate\Support\Carbon::parse($date)->timezone(company()->timezone)->format('d M')
            : $emptyValue;
    };
    $valueClass = function ($value, $type) {
        $value = strtolower((string) $value);

        if ($type === 'status') {
            if (str_contains($value, 'not interested') || str_contains($value, 'not connected')) {
                return 'is-muted';
            }

            if (str_contains($value, 'lost')) {
                return 'is-danger';
            }

            if (str_contains($value, 'connected') || str_contains($value, 'demo done')) {
                return 'is-success';
            }

            if (str_contains($value, 'pending') || str_contains($value, 'follow') || str_contains($value, 'call again')) {
                return 'is-warning';
            }
        }

        if ($type === 'interest') {
            if (str_contains($value, 'high')) {
                return 'is-success';
            }

            if (str_contains($value, 'medium')) {
                return 'is-warning';
            }

            if (str_contains($value, 'low')) {
                return 'is-info';
            }
        }

        return 'is-neutral';
    };
@endphp

@foreach ($rows as $row)
    <tr>
        <td><a href="{{ route('lead-contact.show', $row['lead_id']) }}?tab=history" target="_blank" rel="noopener">{{ $row['name'] ?: $emptyValue }}</a></td>
        <td>{{ $row['number'] ?: $emptyValue }}</td>
        <td><span class="employee-lead-table-value is-category">{{ $row['category'] ?: $emptyValue }}</span></td>
        <td>
            <div class="employee-lead-activity-pair">
                <span class="employee-lead-table-value {{ $valueClass($row['status']['current'], 'status') }}" title="Current">{{ $row['status']['current'] ?: $emptyValue }}</span>
                <span class="employee-lead-table-value {{ $valueClass($row['status']['previous'], 'status') }}" title="Previous">{{ $row['status']['previous'] ?: $emptyValue }}</span>
            </div>
        </td>
        <td>
            <div class="employee-lead-activity-pair">
                <span class="employee-lead-table-value {{ $valueClass($row['interest_level']['current'], 'interest') }}" title="Current">{{ $row['interest_level']['current'] ?: $emptyValue }}</span>
                <span class="employee-lead-table-value {{ $valueClass($row['interest_level']['previous'], 'interest') }}" title="Previous">{{ $row['interest_level']['previous'] ?: $emptyValue }}</span>
            </div>
        </td>
        <td>
            <div class="employee-lead-activity-pair">
                <span class="employee-lead-table-value is-date-current" title="Current">{{ $formatActivityDate($row['followup_date']['current']) }}</span>
                <span class="employee-lead-table-value is-date-previous" title="Previous">{{ $formatActivityDate($row['followup_date']['previous']) }}</span>
            </div>
        </td>
        <td>
            @foreach ($row['changes'] as $change)
                <div class="border-bottom pb-2 mb-2">
                    <strong>{{ $change->event_type === 'lead_field_updated' ? ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', $change->field_key ?? ''))) : ucwords(str_replace('_', ' ', $change->event_type)) }}</strong>
                    @if ($change->event_type === 'lead_field_updated')
                        <div>Old: {{ $change->old_value ?? $emptyValue }} &rarr; New: {{ $change->new_value ?? $emptyValue }}</div>
                    @elseif (str_starts_with($change->event_type, 'followup_'))
                        <div>{{ $change->description }}</div>
                    @endif
                    <small class="text-muted">{{ Illuminate\Support\Carbon::parse($change->event_at, 'UTC')->timezone(company()->timezone)->format('d M Y, h:i:s A') }} ({{ company()->timezone }})</small>
                </div>
            @endforeach
        </td>
    </tr>
@endforeach
