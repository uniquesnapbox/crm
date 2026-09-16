<div class="employee-lead-activity">
    <div class="mb-3 text-muted">
        Employee: <strong>{{ $employee->name }}</strong>
        <span class="ml-2">(selected date range)</span>
    </div>

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
