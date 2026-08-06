@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="bg-white rounded p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">{{ $document->exists ? 'Edit Document' : 'Create Document' }}</h4>
                    <p class="text-muted mb-0">Use template merge data as JSON. Example: <code>{"employee_name":"John Doe"}</code></p>
                </div>
                <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">Back</a>
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
                            <label>Template</label>
                            <select name="template_id" class="form-control">
                                <option value="">Select template</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected((string) old('template_id', $document->template_id) === (string) $template->id)>{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $document->title) }}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject', $document->subject) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Document Type</label>
                            <input type="text" name="document_type" class="form-control" value="{{ old('document_type', $document->document_type ?: 'custom') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Context</label>
                            <input type="text" name="module_context" class="form-control" value="{{ old('module_context', $document->module_context ?: 'manual') }}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Owner</label>
                            <select name="owner_id" class="form-control">
                                <option value="">Select owner</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected((string) old('owner_id', $document->owner_id) === (string) $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Client</label>
                            <select name="client_id" class="form-control">
                                <option value="">Select client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string) old('client_id', $document->client_id) === (string) $client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Project</label>
                            <select name="project_id" class="form-control">
                                <option value="">Select project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected((string) old('project_id', $document->project_id) === (string) $project->id)>{{ $project->project_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', optional($document->expires_at)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>

                @php
                    $existingData = $document->workflowData?->data_json ? json_encode(json_decode($document->workflowData->data_json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';
                @endphp
                <div class="form-group">
                    <label>Merge Data JSON</label>
                    <textarea name="merge_data" rows="8" class="form-control" placeholder='{"employee_name":"John Doe","designation":"Sales Executive"}'>{{ old('merge_data', $existingData) }}</textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">{{ $document->exists ? 'Update Document' : 'Save Draft' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
