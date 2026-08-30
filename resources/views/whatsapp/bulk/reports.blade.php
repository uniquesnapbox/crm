@extends('layouts.app')

@push('styles')
    <style>
        .campaign-report-panel {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .report-strip {
            display: grid;
            grid-template-columns: 1.35fr 1.35fr repeat(5, minmax(130px, 1fr));
            gap: 12px;
            align-items: stretch;
        }

        .report-strip-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            padding: 0.85rem 0.95rem;
            min-height: 104px;
        }

        .report-strip-card.tight {
            min-height: 104px;
        }

        .report-strip-card .f-12,
        .report-strip-card .metric-label,
        .report-strip-card .text-muted {
            white-space: nowrap;
        }

        .campaign-metric {
            min-height: 78px;
            border: 0;
            border-radius: 0;
            padding: 0;
            background: transparent;
        }

        .campaign-metric .metric-label {
            color: #64748b;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .campaign-metric .metric-value { color: #0f172a; font-size: 1.7rem; font-weight: 700; line-height: 1.2; }
        .campaign-metric.sent .metric-value { color: #15803d; }
        .campaign-metric.failed .metric-value { color: #b91c1c; }
        .campaign-metric.pending .metric-value { color: #a16207; }

        .campaign-metrics-row {
            margin: 0;
            gap: 0;
        }

        .campaign-metric-col {
            padding: 0;
        }

        .report-status {
            display: inline-flex;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: capitalize;
        }

        .report-status.sent, .report-status.completed { background: #dcfce7; color: #15803d; }
        .report-status.pending, .report-status.paused { background: #fef3c7; color: #a16207; }
        .report-status.failed { background: #fee2e2; color: #b91c1c; }
        .report-status.running, .report-status.queued { background: #dbeafe; color: #1d4ed8; }
        .report-status.stopped { background: #e2e8f0; color: #475569; }

        .report-detail-row { border-bottom: 1px solid #edf2f7; padding: 0.72rem 0; }
        .report-detail-row:last-child { border-bottom: 0; }
        .report-message { white-space: pre-line; max-width: 350px; }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="report-strip mb-4">
            <div class="report-strip-card tight">
                <form method="GET" action="{{ route('whatsapp.bulk.reports') }}">
                    <label for="campaign" class="f-14 text-dark-grey mb-2">Select Campaign</label>
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <select name="campaign" id="campaign" class="form-control select-picker flex-grow-1" data-live-search="true">
                            <option value="">Select a bulk WhatsApp campaign</option>
                            @foreach ($campaignOptions as $option)
                                @php $optionDate = $option->started_at ?: $option->created_at; @endphp
                                <option value="{{ $option->id }}" @selected($campaign?->id === $option->id)>
                                    {{ $option->name }} - {{ $optionDate?->format('d M Y, h:i A') }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary" style="white-space: nowrap;"><i class="fa fa-bar-chart mr-1"></i> View</button>
                    </div>
                </form>
            </div>

        @if (!$campaign)
            <div class="report-strip-card text-center text-muted d-flex align-items-center justify-content-center">
                <div>
                    <i class="fa fa-bar-chart fa-2x d-block mb-2 text-light"></i>
                    Select a campaign to view report.
                </div>
            </div>
        @else
            @php
                $campaignStatus = strtolower((string) $campaign->status);
                $startedAt = $campaign->started_at ?: $campaign->created_at;
            @endphp
            <div class="report-strip-card tight d-flex flex-column justify-content-between">
                <div class="d-flex align-items-center justify-content-between" style="gap: 10px;">
                    <div class="text-truncate">
                        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                            <h5 class="mb-0 text-truncate">{{ $campaign->name }}</h5>
                            <span class="report-status {{ $campaignStatus }}">{{ $campaignStatus }}</span>
                        </div>
                        <div class="f-12 text-muted mt-1 text-truncate">Started {{ $startedAt?->format('d M Y, h:i A') }}</div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="f-12 text-muted">Progress</div>
                        <div class="f-20 f-w-600 text-primary">{{ $summary['progress'] }}%</div>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 6px; border-radius: 999px; background: #e2e8f0;">
                    <div class="progress-bar bg-primary" style="width: {{ $summary['progress'] }}%"></div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 f-12 text-muted" style="gap: 12px;">
                    <span class="text-truncate">End: {{ $campaign->completed_at?->format('d M Y, h:i A') ?: 'In progress' }}</span>
                    <span class="text-truncate">Delay: {{ $campaign->delay_min_seconds ?: 8 }}-{{ $campaign->delay_max_seconds ?: 20 }} sec</span>
                </div>
            </div>

            <div class="row mb-4 campaign-metrics-row flex-nowrap">
                <div class="campaign-metric-col"><div class="report-strip-card"><div class="campaign-metric"><div class="metric-label">Total Messages</div><div class="metric-value">{{ $summary['total'] }}</div></div></div></div>
                <div class="campaign-metric-col"><div class="report-strip-card"><div class="campaign-metric sent"><div class="metric-label">Sent</div><div class="metric-value">{{ $summary['sent'] }}</div></div></div></div>
                <div class="campaign-metric-col"><div class="report-strip-card"><div class="campaign-metric failed"><div class="metric-label">Failed</div><div class="metric-value">{{ $summary['failed'] }}</div></div></div></div>
                <div class="campaign-metric-col"><div class="report-strip-card"><div class="campaign-metric pending"><div class="metric-label">Pending / Remaining</div><div class="metric-value">{{ $summary['pending'] }}</div></div></div></div>
                <div class="campaign-metric-col"><div class="report-strip-card"><div class="campaign-metric"><div class="metric-label">Progress</div><div class="metric-value">{{ $summary['progress'] }}%</div></div></div></div>
            </div>
        @endif
        </div>
        @if ($campaign)
            <div class="campaign-report-panel overflow-hidden">
                <div class="p-4 border-bottom"><h5 class="mb-0">Recipient Delivery Status</h5></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Recipient</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Sent At</th>
                                <th>Reference / Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recipients as $recipient)
                                @php $recipientStatus = strtolower((string) $recipient->status); @endphp
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $recipient->lead_name ?: $recipient->lead?->client_name ?: 'Lead' }}</div>
                                        <small class="text-muted">{{ $recipient->response_data['content_type'] ?? 'text' }}</small>
                                    </td>
                                    <td>{{ $recipient->phone ?: 'No phone' }}</td>
                                    <td><span class="report-status {{ $recipientStatus }}">{{ $recipientStatus }}</span></td>
                                    <td>{{ $recipient->sent_at?->format('d M Y, h:i A') ?: '--' }}</td>
                                    <td class="report-message">
                                        @if ($recipient->error_message)
                                            <span class="text-danger">{{ $recipient->error_message }}</span>
                                        @else
                                            <span class="text-muted">{{ $recipient->provider_message_id ?: '--' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">No recipient records available for this campaign.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($recipients->hasPages())
                    <div class="px-4 py-3 border-top">{{ $recipients->links() }}</div>
                @endif
            </div>
        @endif
    </div>
@endsection
