<style>
    .lead-history-feed .card-body {
        padding: 10px 12px;
    }

    .lead-history-title {
        margin: 0 0 8px;
        font-size: 20px;
        font-weight: 700;
    }

    .lead-history-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        border-top: 1px solid #edf1f7;
        font-size: 12px;
        line-height: 1.2;
    }

    .lead-history-row:first-of-type {
        border-top: 0;
    }

    .lead-history-status {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        min-width: 94px;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .lead-history-status.status-success { background: #e7f8ee; color: #187a45; }
    .lead-history-status.status-warning { background: #fff4d6; color: #9b6a00; }
    .lead-history-status.status-info { background: #e9f2ff; color: #1e5bba; }
    .lead-history-status.status-primary { background: #ebf0ff; color: #334ebf; }

    .lead-history-main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        overflow: hidden;
    }

    .lead-history-item-title {
        font-weight: 600;
        color: #1f2d45;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lead-history-item-desc {
        color: #5d6d89;
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .lead-history-item-meta {
        color: #8390a8;
        margin-left: auto;
        white-space: nowrap;
    }

    .lead-history-actions {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 4px;
    }

    .lead-history-actions .form-control {
        height: 26px;
        min-height: 26px;
        padding: 2px 6px;
        font-size: 11px;
        width: 118px;
    }

    .lead-history-actions .btn {
        padding: 2px 8px;
        font-size: 11px;
        line-height: 1.3;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded lead-history-feed">
            <div class="card-body">
                <h5 class="lead-history-title">
                    <i class="fa fa-history text-primary mr-1"></i> Lead History
                </h5>

                @forelse ($historyItems as $item)
                    @php
                        $icon = $item['type'] === 'note' ? 'fa-sticky-note'
                            : (($item['type'] ?? '') === 'followup' ? 'fa-calendar-check' : (($item['type'] ?? '') === 'updated' ? 'fa-edit' : 'fa-plus-circle'));
                        $badge = $item['type'] === 'note' ? 'status-info'
                            : (($item['type'] ?? '') === 'followup' ? 'status-success' : (($item['type'] ?? '') === 'updated' ? 'status-warning' : 'status-primary'));
                        $itemStatusLabel = ($item['type'] ?? '') === 'followup'
                            ? ucfirst((string) ($item['followup_status'] ?? 'pending'))
                            : (($item['type'] ?? '') === 'note' ? 'Note' : (($item['type'] ?? '') === 'updated' ? 'Updated' : 'Created'));
                    @endphp
                    <div class="lead-history-row">
                        <span class="lead-history-status {{ $badge }}">
                            <i class="fa {{ $icon }}"></i>{{ $itemStatusLabel }}
                        </span>

                        <div class="lead-history-main">
                            <span class="lead-history-item-title">{{ $item['title'] }}</span>
                            <span class="lead-history-item-desc">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($item['description'] ?? '--')), 80) }}</span>
                            <span class="lead-history-item-meta">
                                {{ $item['meta'] ?? 'By System' }} | {{ \Carbon\Carbon::parse($item['timestamp'])->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format) }}
                            </span>
                        </div>

                        @if (($item['type'] ?? '') === 'followup' && !empty($item['followup_id']))
                            <div class="lead-history-actions">
                                @if (!empty($item['can_update_followup_status']))
                                    <select class="form-control form-control-sm js-history-followup-status"
                                        id="history-followup-status-{{ $item['followup_id'] }}-{{ $loop->index }}"
                                        name="history_followup_status[{{ $item['followup_id'] }}]"
                                        aria-label="Follow-up status"
                                        data-followup-id="{{ $item['followup_id'] }}">
                                        <option value="pending" @selected(($item['followup_status'] ?? 'pending') === 'pending')>Pending</option>
                                        <option value="completed" @selected(($item['followup_status'] ?? '') === 'completed')>Completed</option>
                                        <option value="canceled" @selected(($item['followup_status'] ?? '') === 'canceled')>Canceled</option>
                                    </select>
                                @endif

                                @if (!empty($item['followup_edit_url']) && !empty($item['can_edit_followup']))
                                    <a href="javascript:;" class="btn btn-sm btn-outline-primary js-history-edit-followup"
                                        data-url="{{ $item['followup_edit_url'] }}">Edit</a>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-2">
                        No activity found for this lead yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    $('body').off('change.historyFollowupStatus').on('change.historyFollowupStatus', '.js-history-followup-status', function() {
        const id = $(this).data('followup-id');
        const status = $(this).val();

        $.easyAjax({
            url: "{{ route('lead-contact.change_follow_up_status') }}",
            type: 'POST',
            blockUI: false,
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                status: status
            },
            success: function(response) {
                if (response.status === 'success') {
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Follow-up status updated');
                    }
                    // Re-render the history row from the database so the
                    // title, badge and selected status always stay in sync.
                    window.location.reload();
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Unable to update follow-up status');
                }
                window.location.reload();
            }
        });
    });

    $('body').off('click.historyEditFollowup').on('click.historyEditFollowup', '.js-history-edit-followup', function() {
        const url = $(this).data('url');
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });
</script>
