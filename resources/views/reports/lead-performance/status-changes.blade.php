<div class="employee-lead-activity">
    @php
        $summaryBadgeClass = function ($value) {
            $value = strtolower((string) $value);

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

            if (str_contains($value, 'high')) {
                return 'is-success';
            }

            if (str_contains($value, 'medium')) {
                return 'is-warning';
            }

            return 'is-info';
        };
    @endphp

    <div class="mb-3 text-muted">
        Employee: <strong>{{ $employee->name }}</strong>
        <span class="ml-2">(selected date range)</span>
    </div>

    @if (!empty($summary))
        <div class="employee-lead-activity-summary mb-3">
            @foreach ($summary as $summaryGroup)
                <div class="employee-lead-activity-summary-group">
                    <strong>{{ $summaryGroup['label'] }}:</strong>
                    @foreach ($summaryGroup['items'] as $summaryItem)
                        <span class="employee-lead-activity-summary-item {{ $summaryBadgeClass($summaryItem['value']) }}">
                            {{ $summaryItem['value'] }} = {{ $summaryItem['count'] }}
                        </span>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    @if ($rows->isEmpty())
        <div class="text-center py-4 text-muted">
            No lead activity found in the selected date range.
        </div>
    @else
        <div class="table-responsive employee-lead-activity-table-wrap">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Number</th>
                        <th>Lead Category</th>
                        <th>Lead Status</th>
                        <th>Interest Level</th>
                        <th>Follow-up Date</th>
                    </tr>
                </thead>
                <tbody class="employee-lead-activity-rows">
                    @include('reports.lead-performance.status-changes-rows', ['rows' => $rows])
                </tbody>
            </table>
        </div>

        <div class="employee-lead-activity-load-more text-center mt-3">
            @if ($hasMore)
                <button type="button" class="btn btn-sm btn-outline-primary js-load-more-lead-activity"
                    data-url="{{ route('lead-performance-report.employee.status_changes', ['employee' => $employee->id], false) }}"
                    data-page="{{ $nextPage }}">
                    Load More
                </button>
            @endif
        </div>
    @endif
</div>
