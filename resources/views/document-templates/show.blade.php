@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="bg-white rounded p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h4 class="mb-1">{{ $template->name }}</h4>
                    <p class="text-muted mb-0">{{ $template->subject }}</p>
                </div>
                <div>
                    <a href="{{ route('document-templates.edit', $template) }}" class="btn btn-outline-secondary">Edit</a>
                    <a href="{{ route('document-templates.preview', $template) }}" target="_blank" class="btn btn-outline-primary">Preview</a>
                    <form action="{{ route('document-templates.duplicate', $template) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">Duplicate</button>
                    </form>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row mb-4">
                <div class="col-md-3"><strong>Type:</strong><br>{{ $template->document_type }}</div>
                <div class="col-md-3"><strong>Category:</strong><br>{{ $template->category }}</div>
                <div class="col-md-3"><strong>Approval:</strong><br>{{ $template->requires_approval ? 'Required' : 'No' }}</div>
                <div class="col-md-3"><strong>Signature:</strong><br>{{ $template->requires_signature ? 'Required' : 'No' }}</div>
            </div>

            <div class="mb-4">
                <h5>Detected Merge Tags</h5>
                @if (empty($mergeTags))
                    <p class="text-muted mb-0">No merge tags found.</p>
                @else
                    @foreach ($mergeTags as $tag)
                        <span class="badge badge-light border mr-2 mb-2">{{ $tag }}</span>
                    @endforeach
                @endif
            </div>

            <div class="mb-4">
                <h5>Template Content</h5>
                <div class="border rounded p-3">
                    {!! $template->content_html !!}
                </div>
            </div>

            <div>
                <h5>Workflow Usage</h5>
                <p class="mb-0">Used in {{ $template->workflows_count }} document workflow(s).</p>
            </div>
        </div>
    </div>
@endsection
