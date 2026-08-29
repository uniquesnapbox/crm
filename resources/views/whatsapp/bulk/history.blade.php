@extends('layouts.app')

@push('styles')
    <style>
        .campaign-page-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .campaign-page-card .table th {
            border-top: 0;
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .campaign-page-card .table td {
            vertical-align: middle;
        }

        .campaign-count {
            display: inline-flex;
            min-width: 2.2rem;
            justify-content: center;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            font-weight: 700;
            background: #f1f5f9;
            color: #334155;
        }

        .campaign-count.is-sent { background: #dcfce7; color: #15803d; }
        .campaign-count.is-failed { background: #fee2e2; color: #b91c1c; }
        .campaign-count.is-pending { background: #fef3c7; color: #a16207; }

        .campaign-status {
            display: inline-flex;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: capitalize;
        }

        .campaign-status.completed { background: #dcfce7; color: #15803d; }
        .campaign-status.running, .campaign-status.queued { background: #dbeafe; color: #1d4ed8; }
        .campaign-status.paused { background: #fef3c7; color: #a16207; }
        .campaign-status.failed { background: #fee2e2; color: #b91c1c; }
        .campaign-status.stopped { background: #e2e8f0; color: #475569; }

        .campaign-progress { min-width: 110px; }
        .campaign-progress .progress { height: 6px; border-radius: 999px; background: #e2e8f0; }
    </style>
@endpush

@section('content')
    <div class="content-wrapper">
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
            <div>
                <h3 class="mb-1">Campaign History</h3>
                <p class="text-muted mb-0">Review all previous bulk WhatsApp campaigns and their delivery results.</p>
            </div>
            <a href="{{ route('whatsapp.bulk.index') }}" class="btn btn-primary mt-2 mt-md-0">
                <i class="fa fa-paper-plane mr-1"></i> New Bulk Campaign
            </a>
        </div>

        <div class="campaign-page-card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Campaign Name</th>
                            <th>Date / Time</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Sent</th>
                            <th class="text-center">Failed</th>
                            <th class="text-center">Pending</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $campaign)
                            @php
                                $progress = $campaign->getAttribute('report_progress');
                                $status = strtolower((string) $campaign->status);
                                $date = $campaign->started_at ?: $campaign->created_at;
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $campaign->name }}</div>
                                    <small class="text-muted">Created by {{ $campaign->creator?->name ?: 'System' }}</small>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $date?->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $date?->format('h:i A') }}</small>
                                </td>
                                <td class="text-center"><span class="campaign-count">{{ $progress['total'] }}</span></td>
                                <td class="text-center"><span class="campaign-count is-sent">{{ $progress['sent'] }}</span></td>
                                <td class="text-center"><span class="campaign-count is-failed">{{ $progress['failed'] }}</span></td>
                                <td class="text-center"><span class="campaign-count is-pending">{{ $progress['pending'] }}</span></td>
                                <td><span class="campaign-status {{ $status }}">{{ $status }}</span></td>
                                <td>
                                    <div class="campaign-progress">
                                        <div class="d-flex justify-content-between f-12 mb-1"><span>{{ $progress['progress'] }}%</span><span class="text-muted">{{ $progress['processed'] }}/{{ $progress['total'] }}</span></div>
                                        <div class="progress"><div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress['progress'] }}%"></div></div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('whatsapp.bulk.reports', ['campaign' => $campaign->id]) }}" class="btn btn-sm btn-outline-primary">View Details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa fa-history fa-2x d-block mb-3 text-light"></i>
                                    No bulk WhatsApp campaign has been created yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($campaigns->hasPages())
                <div class="px-4 py-3 border-top">{{ $campaigns->links() }}</div>
            @endif
        </div>
    </div>
@endsection
