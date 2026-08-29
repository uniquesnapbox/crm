@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@push('styles')
    <style>
        .bulk-whatsapp-shell {
            display: grid;
            grid-template-columns: minmax(0, 1.9fr) minmax(320px, 0.95fr);
            gap: 1.25rem;
            align-items: start;
        }

        .bulk-section-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .bulk-section-card .card-header {
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .bulk-stepper {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .bulk-step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: #fff;
            min-height: 70px;
        }

        .bulk-step.is-active {
            border-color: #1d4ed8;
            box-shadow: 0 10px 22px rgba(29, 78, 216, 0.08);
        }

        .bulk-step-number {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            background: #94a3b8;
            flex: 0 0 auto;
        }

        .bulk-step.is-active .bulk-step-number {
            background: #1d4ed8;
        }

        .bulk-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .bulk-metric {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 0.85rem 0.95rem;
            background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
        }

        .bulk-metric-value {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
        }

        .bulk-metric-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .bulk-flow-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .bulk-flow-list li {
            display: flex;
            gap: 0.75rem;
            padding: 0.9rem 0;
            border-bottom: 1px solid #eef2f7;
        }

        .bulk-flow-list li:last-child {
            border-bottom: 0;
        }

        .bulk-flow-dot {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex: 0 0 auto;
        }

        .bulk-sidebar {
            position: sticky;
            top: 1rem;
        }

        .bulk-preview-table td,
        .bulk-preview-table th,
        .bulk-log-table td,
        .bulk-log-table th {
            vertical-align: middle;
        }

        .bulk-muted {
            color: #64748b;
        }

        .bulk-message-wrap textarea {
            min-height: 180px;
        }

        .bulk-toolbar {
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .bulk-preview-panel {
            min-height: 180px;
        }

        @media (max-width: 1199.98px) {
            .bulk-whatsapp-shell {
                grid-template-columns: 1fr;
            }

            .bulk-sidebar {
                position: static;
            }

            .bulk-stepper {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .bulk-stepper {
                grid-template-columns: 1fr;
            }

            .bulk-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('filter-section')
    <x-filters.filter-box class="bulk-toolbar">
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text"
                       class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                       id="datatableRange" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('modules.invoices.type')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="type" id="type">
                    <option value="lead" @selected(request('type', 'lead') === 'lead')>@lang('modules.lead.lead')</option>
                    <option value="client" @selected(request('type') === 'client')>@lang('modules.lead.client')</option>
                </select>
            </div>
        </div>

        <div class="task-search d-flex py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey">
                            <i class="fa fa-search f-13 text-dark-grey"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                           placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>

        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>

        <x-filters.more-filter-box>
            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="date_filter_on">@lang('app.dateFilterOn')</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" name="date_filter_on" id="date_filter_on">
                        <option value="created_at">@lang('app.createdOn')</option>
                        <option value="updated_at">@lang('app.updatedOn')</option>
                    </select>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_category_id">@lang('modules.lead.leadCategory')</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_category_id" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_status_id">@lang('modules.lead.leadStatus')</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_status_id" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_interest_level">Interest Level</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_interest_level" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="very_high">Very High</option>
                    </select>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_source_id">@lang('modules.lead.leadSource')</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_source_id" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_addedBy">@lang('app.addedBy')</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_addedBy" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($employees as $item)
                            <x-user-option :user="$item" />
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_assigned_to">@lang('modules.tasks.assignTo')</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_assigned_to" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($employees as $item)
                            <x-user-option :user="$item" />
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filters.more-filter-box>
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="bulk-whatsapp-shell">
            <div class="bulk-main">
                <div class="bulk-section-card mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between mb-3">
                            <div>
                                <h4 class="mb-1 f-20 f-w-600">Bulk WhatsApp</h4>
                                <div class="bulk-muted">Select filtered leads, preview personalized messages, then send in bulk.</div>
                            </div>
                            <div class="text-right">
                                <div class="badge badge-success px-3 py-2" style="font-size: 13px;">
                                    WhatsApp Connected: {{ $sessionKey ?: 'Not configured' }}
                                </div>
                                <div class="bulk-muted mt-2">Session {{ $sessionKey ?: '--' }}</div>
                            </div>
                        </div>

                        <div class="bulk-stepper">
                            <div class="bulk-step is-active">
                                <div class="bulk-step-number">1</div>
                                <div>
                                    <div class="f-w-600">Select Leads</div>
                                    <div class="bulk-muted f-12">Filter and multi-select contacts.</div>
                                </div>
                            </div>
                            <div class="bulk-step">
                                <div class="bulk-step-number">2</div>
                                <div>
                                    <div class="f-w-600">Compose</div>
                                    <div class="bulk-muted f-12">Template or manual message.</div>
                                </div>
                            </div>
                            <div class="bulk-step">
                                <div class="bulk-step-number">3</div>
                                <div>
                                    <div class="f-w-600">Preview</div>
                                    <div class="bulk-muted f-12">Check personalized output.</div>
                                </div>
                            </div>
                            <div class="bulk-step">
                                <div class="bulk-step-number">4</div>
                                <div>
                                    <div class="f-w-600">Send</div>
                                    <div class="bulk-muted f-12">Track sent and failed logs.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bulk-section-card mb-3">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 f-w-600">Select Leads</h5>
                            <div class="bulk-muted f-12">Choose the filtered leads/contacts you want to message.</div>
                        </div>
                        <div class="badge badge-light px-3 py-2">
                            Selected: <span id="selected-count">0</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                            <div class="bulk-muted f-12">
                                Use the table checkboxes to pick individual leads, or the header checkbox to select the current page.
                            </div>
                            <div class="bulk-muted f-12">
                                Use filters above to narrow the lead list before selecting recipients.
                            </div>
                        </div>

                        <div class="d-flex flex-column w-100 rounded bg-white table-responsive">
                            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100', 'id' => 'lead-contact-table']) !!}
                        </div>
                    </div>
                </div>

                <div class="bulk-section-card mb-3">
                    <div class="card-header bg-white py-3 px-4">
                        <h5 class="mb-1 f-w-600">Compose Message</h5>
                        <div class="bulk-muted f-12">Choose a template or write your own WhatsApp message.</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="f-14 f-w-500 mb-0">Choose Template (Optional)</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="open-template-modal">
                                            + New Template
                                        </button>
                                    </div>
                                    <select class="form-control select-picker" id="template_id" data-live-search="true" data-size="8">
                                        <option value="">-- Select Template --</option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}" data-message="{{ e($template->message) }}">
                                                {{ $template->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group bulk-message-wrap">
                                    <label class="f-14 f-w-500" for="campaign_name">Campaign Name</label>
                                    <input type="text" class="form-control" id="campaign_name" placeholder="Bulk WhatsApp Campaign">
                                </div>

                                <div class="form-group bulk-message-wrap">
                                    <label class="f-14 f-w-500" for="bulk_message">Message *</label>
                                    <textarea class="form-control" id="bulk_message" placeholder="Type your WhatsApp message here...">{{ $messagePlaceholder }}</textarea>
                                </div>

                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="bulk-muted f-12">
                                        Available variables: <code>@{{name}}</code> <code>@{{company}}</code> <code>@{{mobile}}</code> <code>@{{lead_id}}</code> <code>@{{status}}</code>
                                    </div>
                                    <div class="bulk-muted f-12">
                                        <span id="message-counter">0</span>/5000
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 mt-4 mt-lg-0">
                                <div class="bulk-metric mb-3">
                                    <div class="bulk-metric-value" id="preview-ready-count">0</div>
                                    <div class="bulk-metric-label">Recipients ready to send</div>
                                </div>
                                <div class="bulk-metric mb-3">
                                    <div class="bulk-metric-value" id="preview-missing-count">0</div>
                                    <div class="bulk-metric-label">Recipients missing phone</div>
                                </div>
                                <div class="bulk-metric">
                                    <div class="bulk-metric-value" id="campaign-progress-value">0%</div>
                                    <div class="bulk-metric-label">Campaign progress</div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar" id="campaign-progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary" id="preview-whatsapp">
                                Preview
                            </button>
                            <button type="button" class="btn btn-primary" id="send-whatsapp" disabled>
                                Send Bulk WhatsApp
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clear-selection">
                                Clear Selection
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bulk-section-card mb-3">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 f-w-600">Preview</h5>
                            <div class="bulk-muted f-12">Check each recipient before sending.</div>
                        </div>
                        <div class="badge badge-light px-3 py-2" id="preview-count-badge">0 recipients</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive bulk-preview-panel">
                            <table class="table table-bordered mb-0 bulk-preview-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 18%">Lead</th>
                                        <th style="width: 14%">Phone</th>
                                        <th style="width: 12%">Status</th>
                                        <th>Preview Message</th>
                                    </tr>
                                </thead>
                                <tbody id="preview-table-body">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            Select leads and click Preview to see personalized message output here.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bulk-section-card">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 f-w-600">Sent / Failed Logs</h5>
                            <div class="bulk-muted f-12">Review sending results after the campaign runs.</div>
                        </div>
                        <div class="badge badge-light px-3 py-2" id="logs-count-badge">0 logs</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0 bulk-log-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 18%">Lead</th>
                                        <th style="width: 14%">Phone</th>
                                        <th style="width: 12%">Status</th>
                                        <th>Error / Reference</th>
                                        <th style="width: 14%">Sent At</th>
                                    </tr>
                                </thead>
                                <tbody id="log-table-body">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            No campaign logs yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bulk-sidebar">
                <div class="bulk-section-card mb-3">
                    <div class="card-header bg-white py-3 px-4">
                        <h5 class="mb-1 f-w-600">FLOW: Bulk WhatsApp Sending</h5>
                    </div>
                    <div class="card-body px-4 py-2">
                        <ul class="bulk-flow-list">
                            <li>
                                <div class="bulk-flow-dot" style="background:#16a34a;color:#fff;">1</div>
                                <div>
                                    <div class="f-w-600">Select Leads</div>
                                    <div class="bulk-muted f-12">Filter and select multiple leads/contacts.</div>
                                </div>
                            </li>
                            <li>
                                <div class="bulk-flow-dot" style="background:#16a34a;color:#fff;">2</div>
                                <div>
                                    <div class="f-w-600">Compose Message</div>
                                    <div class="bulk-muted f-12">Write a message or load a template with variables.</div>
                                </div>
                            </li>
                            <li>
                                <div class="bulk-flow-dot" style="background:#16a34a;color:#fff;">3</div>
                                <div>
                                    <div class="f-w-600">Preview</div>
                                    <div class="bulk-muted f-12">Review each recipient before sending.</div>
                                </div>
                            </li>
                            <li>
                                <div class="bulk-flow-dot" style="background:#16a34a;color:#fff;">4</div>
                                <div>
                                    <div class="f-w-600">Send</div>
                                    <div class="bulk-muted f-12">Dispatch in bulk through the WhatsApp bridge.</div>
                                </div>
                            </li>
                            <li>
                                <div class="bulk-flow-dot" style="background:#16a34a;color:#fff;">5</div>
                                <div>
                                    <div class="f-w-600">Progress / Logs</div>
                                    <div class="bulk-muted f-12">Track sent, failed, and message logs.</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="bulk-section-card mb-3">
                    <div class="card-header bg-white py-3 px-4">
                        <h5 class="mb-1 f-w-600">Summary</h5>
                    </div>
                    <div class="card-body px-4 py-3">
                        <div class="bulk-summary-grid">
                            <div class="bulk-metric">
                                <div class="bulk-metric-value" id="summary-total">0</div>
                                <div class="bulk-metric-label">Selected</div>
                            </div>
                            <div class="bulk-metric">
                                <div class="bulk-metric-value" id="summary-ready">0</div>
                                <div class="bulk-metric-label">Ready</div>
                            </div>
                            <div class="bulk-metric">
                                <div class="bulk-metric-value" id="summary-sent">0</div>
                                <div class="bulk-metric-label">Sent</div>
                            </div>
                            <div class="bulk-metric">
                                <div class="bulk-metric-value" id="summary-failed">0</div>
                                <div class="bulk-metric-label">Failed</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bulk-section-card">
                    <div class="card-header bg-white py-3 px-4">
                        <h5 class="mb-1 f-w-600">Functions</h5>
                    </div>
                    <div class="card-body px-4 py-3">
                        <div class="bulk-muted f-13 mb-2">What this page does:</div>
                        <ul class="mb-0 pl-3">
                            <li>Filter leads by status, source, category, and more.</li>
                            <li>Select individual rows or the full visible page.</li>
                            <li>Use templates with variables for fast messaging.</li>
                            <li>Preview before sending.</li>
                            <li>Send in bulk via the WhatsApp bridge.</li>
                            <li>Track success and failure logs.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="template-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Save WhatsApp Template</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="f-14 f-w-500">Template Name</label>
                        <input type="text" class="form-control" id="template-name" placeholder="Follow-up Template">
                    </div>
                    <div class="form-group">
                        <label class="f-14 f-w-500">Message</label>
                        <textarea class="form-control" id="template-message" rows="8"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-template">Save Template</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        const bulkWhatsAppState = {
            selectedLeadIds: new Set(),
            previewRecipients: [],
            currentCampaignId: null,
            pollingHandle: null,
            pollAttempt: 0,
            pollMaxAttempts: 20
        };

        const bulkWhatsAppRoutes = {
            preview: @json(route('whatsapp.bulk.preview')),
            send: @json(route('whatsapp.bulk.send')),
            templatesStore: @json(route('whatsapp.bulk.templates.store')),
            statusTemplate: @json(route('whatsapp.bulk.status', ['campaign' => '__CAMPAIGN__'])),
            logsTemplate: @json(route('whatsapp.bulk.logs', ['campaign' => '__CAMPAIGN__']))
        };

        function bulkRoute(template, id) {
            return template.replace('__CAMPAIGN__', id);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function statusBadge(status) {
            const map = {
                ready: 'success',
                missing_phone: 'warning',
                pending: 'info',
                sent: 'success',
                failed: 'danger'
            };

            const label = status || 'pending';
            const cls = map[label] || 'secondary';

            return '<span class="badge badge-' + cls + '">' + escapeHtml(label.replace(/_/g, ' ')) + '</span>';
        }

        function updateSelectedCount() {
            $('#selected-count').text(bulkWhatsAppState.selectedLeadIds.size);
            $('#summary-total').text(bulkWhatsAppState.selectedLeadIds.size);
            $('#send-whatsapp').prop('disabled', bulkWhatsAppState.selectedLeadIds.size === 0);
        }

        function setCampaignSummary(summary) {
            $('#summary-ready').text(summary.ready ?? 0);
            $('#summary-sent').text(summary.sent ?? 0);
            $('#summary-failed').text(summary.failed ?? 0);
            $('#preview-ready-count').text(summary.ready ?? 0);
            $('#preview-missing-count').text(summary.missing_phone ?? 0);
            $('#campaign-progress-value').text((summary.progress ?? 0) + '%');
            $('#campaign-progress-bar').css('width', (summary.progress ?? 0) + '%');
        }

        function renderPreviewTable(recipients) {
            bulkWhatsAppState.previewRecipients = recipients || [];
            $('#preview-count-badge').text((recipients || []).length + ' recipients');

            if (!recipients || recipients.length === 0) {
                $('#preview-table-body').html('<tr><td colspan="4" class="text-center text-muted py-5">No preview data available.</td></tr>');
                return;
            }

            const rows = recipients.map(function(recipient) {
                return '<tr>' +
                    '<td><strong>' + escapeHtml(recipient.lead_name || recipient.company_name || ('Lead #' + recipient.lead_id)) + '</strong></td>' +
                    '<td>' + escapeHtml(recipient.phone || '--') + '</td>' +
                    '<td>' + statusBadge(recipient.status) + '</td>' +
                    '<td><div style="white-space: pre-wrap;">' + escapeHtml(recipient.preview_message || '') + '</div></td>' +
                '</tr>';
            }).join('');

            $('#preview-table-body').html(rows);
        }

        function renderLogTable(logs) {
            const items = logs || [];
            $('#logs-count-badge').text(items.length + ' logs');

            if (items.length === 0) {
                $('#log-table-body').html('<tr><td colspan="5" class="text-center text-muted py-5">No campaign logs yet.</td></tr>');
                return;
            }

            const rows = items.map(function(item) {
                const refText = item.provider_message_id || item.error_message || '--';
                return '<tr>' +
                    '<td><strong>' + escapeHtml(item.lead_name || ('Lead #' + item.lead_id)) + '</strong></td>' +
                    '<td>' + escapeHtml(item.phone || '--') + '</td>' +
                    '<td>' + statusBadge(item.status) + '</td>' +
                    '<td style="white-space: pre-wrap;">' + escapeHtml(refText) + '</td>' +
                    '<td>' + escapeHtml(item.sent_at || '--') + '</td>' +
                '</tr>';
            }).join('');

            $('#log-table-body').html(rows);
        }

        function selectedLeadIdsPayload() {
            return Array.from(bulkWhatsAppState.selectedLeadIds.values());
        }

        function collectPayload() {
            return {
                lead_ids: selectedLeadIdsPayload(),
                template_id: $('#template_id').val(),
                campaign_name: $('#campaign_name').val(),
                message: $('#bulk_message').val(),
                type: $('#type').val(),
                category_id: $('#filter_category_id').val(),
                source_id: $('#filter_source_id').val(),
                status_id: $('#filter_status_id').val(),
                interest_level: $('#filter_interest_level').val(),
                filter_addedBy: $('#filter_addedBy').val(),
                filter_assignedTo: $('#filter_assigned_to').val(),
                date_filter_on: $('#date_filter_on').val(),
                startDate: $('#datatableRange').val(),
                endDate: null
            };
        }

        function refreshTableSelection() {
            $('#lead-contact-table .select-table-row').each(function() {
                const id = parseInt(this.value, 10);
                const checked = bulkWhatsAppState.selectedLeadIds.has(id);
                $(this).prop('checked', checked);
                $(this).closest('tr').toggleClass('table-active', checked);
            });

            const visibleCheckboxes = $('#lead-contact-table .select-table-row:not(:disabled)');
            const checkedVisible = visibleCheckboxes.filter(':checked').length;
            const totalVisible = visibleCheckboxes.length;

            const selectAll = document.getElementById('select-all-table');
            if (selectAll) {
                selectAll.checked = totalVisible > 0 && checkedVisible === totalVisible;
                selectAll.indeterminate = checkedVisible > 0 && checkedVisible < totalVisible;
            }
        }

        function syncSelectionFromCheckbox(checkbox) {
            const id = parseInt(checkbox.value, 10);
            if (Number.isNaN(id)) {
                return;
            }

            if (checkbox.checked) {
                bulkWhatsAppState.selectedLeadIds.add(id);
            } else {
                bulkWhatsAppState.selectedLeadIds.delete(id);
            }

            $(checkbox).closest('tr').toggleClass('table-active', checkbox.checked);
            updateSelectedCount();
            refreshTableSelection();
        }

        window.dataTableRowCheck = function(id) {
            const checkbox = document.getElementById('datatable-row-' + id);
            if (!checkbox) {
                return;
            }

            syncSelectionFromCheckbox(checkbox);
        };

        window.selectAllTable = function(source) {
            const checkboxes = document.querySelectorAll('#lead-contact-table .select-table-row:not(:disabled)');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = source.checked;
                const id = parseInt(checkbox.value, 10);
                if (source.checked) {
                    bulkWhatsAppState.selectedLeadIds.add(id);
                } else {
                    bulkWhatsAppState.selectedLeadIds.delete(id);
                }
                $(checkbox).closest('tr').toggleClass('table-active', source.checked);
            });

            updateSelectedCount();
        };

        window.resetActionButtons = function() {
            bulkWhatsAppState.selectedLeadIds.clear();
            updateSelectedCount();
            refreshTableSelection();
        };

        function showBulkAlert(message, type) {
            const icon = type === 'success' ? 'success' : 'warning';

            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: message,
                icon: icon,
                confirmButtonText: "@lang('app.ok')",
                customClass: {
                    confirmButton: 'btn btn-primary'
                },
                buttonsStyling: false
            });
        }

        function updateCampaignFromResponse(data) {
            if (!data) {
                return;
            }

            if (data.summary) {
                setCampaignSummary(data.summary);
            }

            if (Array.isArray(data.logs)) {
                renderLogTable(data.logs);
            }

            if (data.campaign && data.campaign.id) {
                bulkWhatsAppState.currentCampaignId = data.campaign.id;
            }
        }

        function fetchCampaignStatus(campaignId) {
            if (!campaignId) {
                return;
            }

            $.ajax({
                url: bulkRoute(bulkWhatsAppRoutes.statusTemplate, campaignId),
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.data) {
                        updateCampaignFromResponse(response.data);
                        const status = response.data.campaign ? response.data.campaign.status : '';

                        if (['completed', 'failed'].includes(status)) {
                            clearInterval(bulkWhatsAppState.pollingHandle);
                            bulkWhatsAppState.pollingHandle = null;
                        }
                    }
                }
            });
        }

        function startCampaignPolling(campaignId) {
            clearInterval(bulkWhatsAppState.pollingHandle);
            bulkWhatsAppState.pollAttempt = 0;
            bulkWhatsAppState.pollingHandle = setInterval(function() {
                bulkWhatsAppState.pollAttempt += 1;
                fetchCampaignStatus(campaignId);

                if (bulkWhatsAppState.pollAttempt >= bulkWhatsAppState.pollMaxAttempts) {
                    clearInterval(bulkWhatsAppState.pollingHandle);
                    bulkWhatsAppState.pollingHandle = null;
                }
            }, 4000);
        }

        function submitCampaign(endpoint, buttonSelector) {
            const selectedIds = selectedLeadIdsPayload();
            if (selectedIds.length === 0) {
                showBulkAlert('Please select at least one lead/contact.', 'warning');
                return;
            }

            const payload = collectPayload();
            payload.lead_ids = selectedIds;

            const $button = $(buttonSelector);
            $button.prop('disabled', true);

            $.ajax({
                url: endpoint,
                method: 'POST',
                dataType: 'json',
                data: payload,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status !== 'success') {
                        showBulkAlert(response.message || 'Something went wrong.', 'warning');
                        return;
                    }

                    updateCampaignFromResponse(response.data);

                    if (response.data.preview || response.data.recipients) {
                        renderPreviewTable(response.data.preview || response.data.recipients || []);
                    }

                    if (response.data && response.data.campaign && response.data.campaign.id) {
                        fetchCampaignStatus(response.data.campaign.id);
                        startCampaignPolling(response.data.campaign.id);
                    }

                    showBulkAlert(response.message || 'Request processed successfully.', 'success');
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to process the bulk WhatsApp request.';
                    showBulkAlert(message, 'warning');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        }

        $('#lead-contact-table').on('change', '.select-table-row', function() {
            syncSelectionFromCheckbox(this);
        });

        $('#lead-contact-table').on('draw.dt', function() {
            refreshTableSelection();
        });

        $('#lead-contact-table').on('preXhr.dt', function(e, settings, data) {
            const dateRangePicker = $('#datatableRange').data('daterangepicker');
            let startDate = $('#datatableRange').val();
            let endDate = null;

            if (startDate === '') {
                startDate = null;
            } else if (dateRangePicker) {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            Object.assign(data, {
                startDate: startDate,
                endDate: endDate,
                searchText: $('#search-text-field').val(),
                type: $('#type').val(),
                category_id: $('#filter_category_id').val(),
                source_id: $('#filter_source_id').val(),
                status_id: $('#filter_status_id').val(),
                interest_level: $('#filter_interest_level').val(),
                date_filter_on: $('#date_filter_on').val(),
                filter_addedBy: $('#filter_addedBy').val(),
                filter_assignedTo: $('#filter_assigned_to').val()
            });
        });

        function showTable() {
            window.LaravelDataTables['lead-contact-table'].draw(false);
        }

        $('#type, #filter_assigned_to, #filter_category_id, #filter_status_id, #filter_interest_level, #filter_source_id, #date_filter_on, #filter_addedBy')
            .on('change keyup', function() {
                showTable();
                $('#reset-filters').removeClass('d-none');
            });

        $('#search-text-field').on('keyup', function() {
            showTable();
            $('#reset-filters').removeClass('d-none');
        });

        $('#reset-filters').on('click', function() {
            $('#type').val('lead');
            $('#filter_category_id').val('all');
            $('#filter_status_id').val('all');
            $('#filter_interest_level').val('all');
            $('#filter_source_id').val('all');
            $('#filter_addedBy').val('all');
            $('#filter_assigned_to').val('all');
            $('#date_filter_on').val('created_at');
            $('#search-text-field').val('');
            $('#datatableRange').val('');
            $('.filter-box .select-picker').selectpicker('refresh');
            showTable();
            $(this).addClass('d-none');
        });

        $('#preview-whatsapp').on('click', function() {
            submitCampaign(bulkWhatsAppRoutes.preview, '#preview-whatsapp');
        });

        $('#send-whatsapp').on('click', function() {
            submitCampaign(bulkWhatsAppRoutes.send, '#send-whatsapp');
        });

        $('#clear-selection').on('click', function() {
            bulkWhatsAppState.selectedLeadIds.clear();
            updateSelectedCount();
            refreshTableSelection();
            renderPreviewTable([]);
            renderLogTable([]);
        });

        $('#bulk_message').on('input', function() {
            $('#message-counter').text(this.value.length);
        }).trigger('input');

        $('#template_id').on('changed.bs.select change', function() {
            const selected = $(this).find('option:selected');
            const templateMessage = selected.data('message');
            if (templateMessage) {
                $('#bulk_message').val(templateMessage);
                $('#message-counter').text(String(templateMessage).length);
            }
        });

        $('#open-template-modal').on('click', function() {
            $('#template-name').val('');
            $('#template-message').val($('#bulk_message').val());
            $('#template-modal').modal('show');
        });

        $('#save-template').on('click', function() {
            const name = $('#template-name').val();
            const message = $('#template-message').val();

            if (!name || !message) {
                showBulkAlert('Template name and message are required.', 'warning');
                return;
            }

            $.ajax({
                url: bulkWhatsAppRoutes.templatesStore,
                method: 'POST',
                dataType: 'json',
                data: {
                    name: name,
                    message: message
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status !== 'success') {
                        showBulkAlert(response.message || 'Unable to save template.', 'warning');
                        return;
                    }

                    const template = response.data && response.data.template ? response.data.template : null;
                    if (template) {
                        const option = new Option(template.name, template.id, true, true);
                        option.dataset.message = template.message;
                        $('#template_id').append(option).selectpicker('refresh').val(String(template.id)).selectpicker('refresh');
                        $('#bulk_message').val(template.message);
                        $('#message-counter').text(String(template.message).length);
                    }

                    $('#template-modal').modal('hide');
                    showBulkAlert(response.message || 'Template saved.', 'success');
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to save template.';
                    showBulkAlert(message, 'warning');
                }
            });
        });

        updateSelectedCount();
        refreshTableSelection();
    </script>
@endpush
