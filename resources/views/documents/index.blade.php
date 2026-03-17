@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar">
            <x-forms.link-primary :link="route('documents.create')" icon="plus">
                Create Document
            </x-forms.link-primary>
        </div>

        <div class="bg-white rounded p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Documents</h4>
                    <p class="text-muted mb-0">Lead-free document workflow base for offers, contracts, approvals, and signatures.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($documents->isEmpty())
                <div class="alert alert-info mb-0">No documents created yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Number</th>
                            <th>Title</th>
                            <th>Template</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th>Signature</th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td>{{ $document->document_number }}</td>
                                <td>
                                    <a href="{{ route('documents.show', $document) }}">{{ $document->title }}</a>
                                    <div class="text-muted small">{{ $document->document_type }}</div>
                                </td>
                                <td>{{ $document->template?->name ?: 'Manual' }}</td>
                                <td>{{ $document->owner?->name ?: '-' }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($document->status)) }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($document->approval_status)) }}</td>
                                <td>{{ str_replace('_', ' ', ucfirst($document->signature_status)) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this document?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
