@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="bg-white rounded p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h4 class="mb-1">{{ $document->title }}</h4>
                    <p class="text-muted mb-0">{{ $document->document_number }} · {{ $document->document_type }}</p>
                </div>
                <div class="text-right">
                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-outline-secondary">Edit</a>
                    <form action="{{ route('documents.send', $document) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                    <form action="{{ route('documents.cancel', $document) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">Cancel</button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-warning">{{ session('error') }}</div>
            @endif

            <div class="row mb-4">
                <div class="col-md-3"><strong>Status</strong><br>{{ str_replace('_', ' ', ucfirst($document->status)) }}</div>
                <div class="col-md-3"><strong>Approval</strong><br>{{ str_replace('_', ' ', ucfirst($document->approval_status)) }}</div>
                <div class="col-md-3"><strong>Signature</strong><br>{{ str_replace('_', ' ', ucfirst($document->signature_status)) }}</div>
                <div class="col-md-3"><strong>Template</strong><br>{{ $document->template?->name ?: 'Manual' }}</div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3"><strong>Owner</strong><br>{{ $document->owner?->name ?: '-' }}</div>
                <div class="col-md-3"><strong>Client</strong><br>{{ $document->client?->name ?: '-' }}</div>
                <div class="col-md-3"><strong>Project</strong><br>{{ $document->project?->project_name ?: '-' }}</div>
                <div class="col-md-3"><strong>Verification Hash</strong><br><code>{{ $document->verification_hash }}</code></div>
            </div>

            <div class="mb-4">
                <h5>Rendered Document</h5>
                <div class="border rounded p-3">
                    {!! $document->generated_html ?: '<p class="text-muted mb-0">No rendered document body available.</p>' !!}
                </div>
            </div>

            <div class="mb-4">
                <h5>Stored Merge Data</h5>
                <pre class="bg-light border rounded p-3 mb-0">{{ $document->workflowData?->data_json ?: '{}' }}</pre>
            </div>

            <div>
                <h5>Audit Trail</h5>
                @if ($document->auditLogs->isEmpty())
                    <p class="text-muted mb-0">No audit activity yet.</p>
                @else
                    <ul class="list-group">
                        @foreach ($document->auditLogs as $log)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <strong>{{ ucfirst($log->action) }}</strong>
                                    <span class="text-muted">by {{ $log->actor_name ?: 'System' }}</span>
                                </span>
                                <span class="text-muted">{{ optional($log->created_at)->format(company()->date_format . ' ' . company()->time_format) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
