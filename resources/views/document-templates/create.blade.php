@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="bg-white rounded p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $template->exists ? 'Edit Document Template' : 'Create Document Template' }}</h4>
                    <p class="text-muted mb-0">Use placeholders like <code>{{ '{' }}{{ '{employee_name}' }}{{ '}' }}{{ '}' }}</code> inside content.</p>
                </div>
                <a href="{{ route('document-templates.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $formAction }}">
                @csrf
                @if ($formMethod !== 'POST')
                    @method($formMethod)
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category', $template->category ?: 'general') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Document Type</label>
                            <input type="text" name="document_type" class="form-control" value="{{ old('document_type', $template->document_type ?: 'custom') }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}" required>
                </div>

                <div class="form-group">
                    <label>Content HTML</label>
                    <textarea name="content_html" rows="14" class="form-control" required>{{ old('content_html', $template->content_html) }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" class="custom-control-input" id="requires_approval" name="requires_approval" value="1" {{ old('requires_approval', $template->requires_approval) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="requires_approval">Requires Approval</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" class="custom-control-input" id="requires_signature" name="requires_signature" value="1" {{ old('requires_signature', $template->requires_signature) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="requires_signature">Requires Signature</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $template->exists ? $template->is_active : true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ $template->exists ? 'Update Template' : 'Save Template' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
