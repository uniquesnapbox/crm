@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar">
            <x-forms.link-primary :link="route('document-templates.create')" icon="plus">
                Add Document Template
            </x-forms.link-primary>
        </div>

        <div class="bg-white rounded p-4 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-1">Document Templates</h4>
                    <p class="text-muted mb-0">Reusable templates for letters, contracts, approvals, and signatures.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($templates->isEmpty())
                <div class="alert alert-info mb-0">No document templates created yet.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Approval</th>
                            <th>Signature</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($templates as $template)
                            <tr>
                                <td>
                                    <a href="{{ route('document-templates.show', $template) }}">{{ $template->name }}</a>
                                    <div class="text-muted small">{{ $template->subject }}</div>
                                </td>
                                <td>{{ $template->document_type }}</td>
                                <td>{{ $template->category }}</td>
                                <td>{{ $template->requires_approval ? 'Required' : 'No' }}</td>
                                <td>{{ $template->requires_signature ? 'Required' : 'No' }}</td>
                                <td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('document-templates.edit', $template) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('document-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
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
                    {{ $templates->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
