@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@push('styles')
    <style>
        .bulk-whatsapp-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
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
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.45rem;
        }

        .bulk-step {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.55rem 0.65rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fff;
            min-height: 58px;
        }

        .bulk-step.is-active {
            border-color: #1d4ed8;
            box-shadow: 0 10px 22px rgba(29, 78, 216, 0.08);
        }

        .bulk-step-number {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            background: #94a3b8;
            flex: 0 0 auto;
        }

        .bulk-step.is-active .bulk-step-number {
            background: #1d4ed8;
        }

        .bulk-step.is-complete .bulk-step-number {
            background: #16a34a;
        }

        .bulk-step.is-locked {
            opacity: 0.55;
        }

        .bulk-step.is-clickable {
            cursor: pointer;
        }

        .bulk-wizard-step {
            display: none;
        }

        .bulk-wizard-step.is-active {
            display: block;
        }

        .bulk-wizard-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            border-top: 1px solid #eef2f7;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
        }

        .bulk-confirmation {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f8fbff;
            padding: 1rem 1.1rem;
        }

        .bulk-confirmation-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.55rem 0;
            border-bottom: 1px solid #e5eefb;
        }

        .bulk-confirmation-row:last-child {
            border-bottom: 0;
        }

        .bulk-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .bulk-summary-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.45rem;
            flex: 1 1 auto;
            min-width: 0;
        }

        .bulk-summary-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-bottom: 0.45rem;
        }

        .bulk-connection-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: #16a34a;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 0;
            white-space: nowrap;
        }

        .bulk-connection-badge .bulk-connection-key {
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .bulk-summary-strip .bulk-metric {
            min-height: 56px;
            padding: 0.55rem 0.7rem;
        }

        .bulk-summary-strip .bulk-metric-value {
            font-size: 1.1rem;
        }

        .bulk-top-strip {
            display: grid;
            grid-template-columns: minmax(320px, 0.86fr) minmax(0, 1.14fr);
            gap: 0.75rem;
            align-items: start;
        }

        .bulk-top-strip-frame {
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .bulk-whatsapp-page {
            width: 100%;
            max-width: none;
            padding-left: 0 !important;
            padding-right: 0 !important;
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
            width: 100%;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .bulk-toolbar > .select-box {
            flex: 0 0 auto;
        }

        .bulk-toolbar > .task-search {
            flex: 1 1 260px;
            min-width: 260px;
        }

        .bulk-toolbar > .task-search form,
        .bulk-toolbar > .task-search .input-group {
            width: 100%;
        }

        .bulk-toolbar > .ml-auto {
            margin-left: 0 !important;
        }

        .bulk-toolbar .more-filters {
            padding-left: 0 !important;
        }

        .bulk-filter-connection {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            border-radius: 999px;
            background: #16a34a;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .bulk-filter-connection-key {
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .bulk-preview-panel {
            min-height: 180px;
        }

        .bulk-attachment-preview img {
            display: block;
        }

        @media (max-width: 1199.98px) {
            .bulk-whatsapp-shell {
                grid-template-columns: 1fr;
            }

            .bulk-stepper {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bulk-top-strip {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .bulk-stepper {
                grid-template-columns: 1fr;
            }

            .bulk-summary-grid {
                grid-template-columns: 1fr;
            }

            .bulk-summary-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
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

        <div class="bulk-filter-connection">
            <span>WhatsApp Connected:</span>
            <span class="bulk-filter-connection-key">{{ $sessionKey ?? '--' }}</span>
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
                <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_products_services">Products / Services</label>
                <div class="select-filter mb-4">
                    <select class="form-control select-picker" id="filter_products_services" multiple data-live-search="true" data-container="body" data-size="8" title="All">
                        @foreach ($products as $product)
                            <option value="{{ $product->name }}">{{ $product->name }}</option>
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
    <div class="content-wrapper bulk-whatsapp-page px-0">
        <div class="bulk-top-strip-frame mb-3">
            <div class="p-0">
                <div class="bulk-top-strip">
                    <div>
                        <div class="bulk-summary-strip">
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

                    <div>
                        <div class="bulk-stepper" aria-label="Bulk WhatsApp steps">
                            <div class="bulk-step is-active" data-step="1">
                                <div class="bulk-step-number">1</div>
                                <div>
                                    <div class="f-w-600">Select Leads</div>
                                </div>
                            </div>
                            <div class="bulk-step is-locked" data-step="2">
                                <div class="bulk-step-number">2</div>
                                <div>
                                    <div class="f-w-600">Message</div>
                                </div>
                            </div>
                            <div class="bulk-step is-locked" data-step="3">
                                <div class="bulk-step-number">3</div>
                                <div>
                                    <div class="f-w-600">Preview</div>
                                </div>
                            </div>
                            <div class="bulk-step is-locked" data-step="4">
                                <div class="bulk-step-number">4</div>
                                <div>
                                    <div class="f-w-600">Confirm &amp; Send</div>
                                </div>
                            </div>
                            <div class="bulk-step is-locked" data-step="5">
                                <div class="bulk-step-number">5</div>
                                <div>
                                    <div class="f-w-600">Results</div>
                    </div>
                </div>
            </div>
        </div>
                </div>

                <div id="bulk-step-1" class="bulk-section-card mb-3 bulk-wizard-step is-active">
                    <div class="card-header bg-white py-3 px-3 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 f-w-600">Select Leads</h5>
                        </div>
                        <div class="badge badge-light px-3 py-2">
                            Selected: <span id="selected-count">0</span>
                        </div>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="d-flex flex-column w-100 rounded bg-white table-responsive">
                            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100', 'id' => 'lead-contact-table']) !!}
                        </div>

                        <div class="bulk-wizard-footer">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <div class="bulk-muted f-12">Selected leads: <strong id="selected-count-footer">0</strong></div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-selection">
                                    Clear Selection
                                </button>
                            </div>
                            <button type="button" class="btn btn-primary" id="step-1-next">
                                Next: Message <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="bulk-step-2" class="bulk-section-card mb-3 bulk-wizard-step">
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
                                            <option
                                                value="{{ $template->id }}"
                                                data-message="{{ e($template->message) }}"
                                                data-attachment-url="{{ e($template->attachment_url ?? '') }}"
                                                data-attachment-name="{{ e($template->attachment_name ?? '') }}"
                                                data-attachment-mime="{{ e($template->attachment_mime ?? '') }}"
                                                data-attachment-size="{{ e($template->attachment_size ?? '') }}"
                                            >
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

                                <div class="form-group">
                                    <label class="f-14 f-w-500" for="bulk_attachment">Image / Photo Attachment (Optional)</label>
                                    <input type="file" class="form-control-file" id="bulk_attachment" accept="image/*">
                                    <small class="bulk-muted d-block mt-1">JPEG, PNG, WEBP or GIF. The message text will be used as the caption.</small>
                                    <div class="bulk-attachment-preview mt-2" id="bulk-attachment-preview">
                                        <div class="text-muted f-12 border rounded p-3 bg-light">
                                            No image selected. You can upload a photo or use a saved template attachment.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="f-14 f-w-500" for="delay_min_seconds">Min Delay Between Recipients (sec)</label>
                                            <input type="number" min="1" max="600" step="1" class="form-control" id="delay_min_seconds" value="8">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="f-14 f-w-500" for="delay_max_seconds">Max Delay Between Recipients (sec)</label>
                                            <input type="number" min="1" max="600" step="1" class="form-control" id="delay_max_seconds" value="20">
                                        </div>
                                    </div>
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
                                <div class="bulk-confirmation">
                                    <div class="f-w-600 mb-2">Message checklist</div>
                                    <div class="bulk-muted f-13">Use placeholders such as <code>@{{name}}</code> and <code>@{{products_services}}</code> to personalize each message.</div>
                                    <div class="bulk-muted f-13 mt-3">The next step will generate a recipient-by-recipient preview before sending.</div>
                                </div>
                            </div>
                        </div>

                        <div class="bulk-wizard-footer">
                            <button type="button" class="btn btn-outline-secondary" id="step-2-back">
                                <i class="fa fa-arrow-left mr-1"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" id="preview-whatsapp">
                                Next: Preview <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="bulk-step-3" class="bulk-section-card mb-3 bulk-wizard-step">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 f-w-600">Preview</h5>
                            <div class="bulk-muted f-12">Check each recipient before sending.</div>
                        </div>
                        <div class="badge badge-light px-3 py-2" id="preview-count-badge">0 recipients</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="px-4 pt-4">
                            <div class="bulk-preview-attachment" id="preview-attachment-panel">
                                <div class="text-muted f-12 border rounded p-3 bg-light">
                                    Attachment preview will appear here if you upload a photo or choose a template with an image.
                                </div>
                            </div>
                        </div>
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
                    <div class="card-body pt-0">
                        <div class="bulk-wizard-footer mt-0">
                            <button type="button" class="btn btn-outline-secondary" id="step-3-back">
                                <i class="fa fa-arrow-left mr-1"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" id="step-3-next">
                                Next: Confirm <i class="fa fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="bulk-step-4" class="bulk-section-card mb-3 bulk-wizard-step">
                    <div class="card-header bg-white py-3 px-4">
                        <h5 class="mb-1 f-w-600">Final Confirmation</h5>
                        <div class="bulk-muted f-12">Review the final recipient count and message before dispatching.</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="bulk-confirmation" id="final-confirmation-summary">
                            <div class="bulk-confirmation-row"><span class="bulk-muted">Recipients</span><strong id="confirm-recipient-count">0</strong></div>
                            <div class="bulk-confirmation-row"><span class="bulk-muted">Message length</span><strong><span id="confirm-message-length">0</span> characters</strong></div>
                            <div class="bulk-confirmation-row"><span class="bulk-muted">Campaign</span><strong id="confirm-campaign-name">Bulk WhatsApp Campaign</strong></div>
                            <div class="bulk-confirmation-row"><span class="bulk-muted">Attachment</span><span id="confirm-attachment-text">No attachment selected</span></div>
                            <div class="bulk-confirmation-row"><span class="bulk-muted">Delay range</span><strong><span id="confirm-delay-range">8-20</span> sec</strong></div>
                            <div class="bulk-confirmation-row"><span class="bulk-muted">Message</span><span id="confirm-message-text" class="text-right" style="white-space: pre-wrap; max-width: 70%;"></span></div>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0">
                            Messages will be sent through the connected WhatsApp session. Please confirm the preview before clicking Send.
                        </div>
                        <div class="bulk-wizard-footer">
                            <button type="button" class="btn btn-outline-secondary" id="step-4-back">
                                <i class="fa fa-arrow-left mr-1"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary" id="send-whatsapp">
                                <i class="fa fa-paper-plane mr-1"></i> Send
                            </button>
                        </div>
                    </div>
                </div>

                <div id="bulk-step-5" class="bulk-section-card bulk-wizard-step">
                    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-1 f-w-600">Sending Progress &amp; Results</h5>
                            <div class="bulk-muted f-12">Track sent and failed results from the WhatsApp bridge.</div>
                        </div>
                        <div class="badge badge-light px-3 py-2" id="logs-count-badge">0 logs</div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 bulk-toolbar">
                            <div class="bulk-muted f-12" id="campaign-status-text">Campaign status will appear here.</div>
                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                <button type="button" class="btn btn-warning btn-sm d-none" id="pause-campaign">
                                    <i class="fa fa-pause mr-1"></i> Pause
                                </button>
                                <button type="button" class="btn btn-success btn-sm d-none" id="resume-campaign">
                                    <i class="fa fa-play mr-1"></i> Resume
                                </button>
                                <button type="button" class="btn btn-danger btn-sm d-none" id="stop-campaign">
                                    <i class="fa fa-stop mr-1"></i> Stop
                                </button>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="bulk-metric">
                                    <div class="bulk-metric-value" id="preview-ready-count">0</div>
                                    <div class="bulk-metric-label">Recipients ready to send</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="bulk-metric">
                                    <div class="bulk-metric-value" id="preview-missing-count">0</div>
                                    <div class="bulk-metric-label">Recipients missing phone</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="bulk-metric">
                                    <div class="bulk-metric-value" id="preview-pending-count">0</div>
                                    <div class="bulk-metric-label">Recipients pending</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bulk-metric">
                                    <div class="bulk-metric-value" id="campaign-progress-value">0%</div>
                                    <div class="bulk-metric-label">Campaign progress</div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar" id="campaign-progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                        <div class="bulk-wizard-footer">
                            <div class="bulk-muted f-12">Campaign complete hone ke baad sent/failed logs yahan available rahenge.</div>
                            <button type="button" class="btn btn-outline-primary" id="new-campaign">
                                Start New Campaign
                            </button>
                        </div>
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
                    <div class="form-group">
                        <label class="f-14 f-w-500" for="template-attachment">Image / Photo Attachment (Optional)</label>
                        <input type="file" class="form-control-file" id="template-attachment" accept="image/*">
                        <small class="bulk-muted d-block mt-1">This image will be saved with the template and sent with the caption above.</small>
                        <div class="bulk-attachment-preview mt-2" id="template-attachment-preview">
                            <div class="text-muted f-12 border rounded p-3 bg-light">
                                No template image selected.
                            </div>
                        </div>
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
            currentStep: 1,
            selectedLeadIds: new Set(),
            previewRecipients: [],
            currentCampaignId: null,
            currentCampaignStatus: null,
            selectedUploadAttachment: null,
            selectedTemplateAttachment: null,
            currentAttachment: null,
            pollingHandle: null,
            pollAttempt: 0,
            pollMaxAttempts: 20
        };

        const bulkWhatsAppRoutes = {
            preview: @json(route('whatsapp.bulk.preview')),
            send: @json(route('whatsapp.bulk.send')),
            filteredLeadIds: @json(route('whatsapp.bulk.filtered_lead_ids')),
            templatesStore: @json(route('whatsapp.bulk.templates.store')),
            pause: @json(route('whatsapp.bulk.pause', ['campaign' => '__CAMPAIGN__'])),
            resume: @json(route('whatsapp.bulk.resume', ['campaign' => '__CAMPAIGN__'])),
            stop: @json(route('whatsapp.bulk.stop', ['campaign' => '__CAMPAIGN__'])),
            statusTemplate: @json(route('whatsapp.bulk.status', ['campaign' => '__CAMPAIGN__'])),
            logsTemplate: @json(route('whatsapp.bulk.logs', ['campaign' => '__CAMPAIGN__']))
        };

        function bulkRoute(template, id) {
            return template.replace('__CAMPAIGN__', id);
        }

        function showWizardStep(step) {
            const targetStep = Math.max(1, Math.min(5, parseInt(step, 10) || 1));
            bulkWhatsAppState.currentStep = targetStep;

            $('.bulk-wizard-step').removeClass('is-active');
            $('#bulk-step-' + targetStep).addClass('is-active');

            $('.bulk-step').each(function() {
                const stepNumber = parseInt($(this).data('step'), 10);
                $(this)
                    .toggleClass('is-active', stepNumber === targetStep)
                    .toggleClass('is-complete', stepNumber < targetStep)
                    .toggleClass('is-locked', stepNumber > targetStep)
                    .toggleClass('is-clickable', stepNumber <= targetStep);
            });

            if (targetStep === 4) {
                $('#confirm-recipient-count').text(bulkWhatsAppState.previewRecipients.length || bulkWhatsAppState.selectedLeadIds.size);
                $('#confirm-message-length').text(String($('#bulk_message').val() || '').length);
                $('#confirm-campaign-name').text($('#campaign_name').val() || 'Bulk WhatsApp Campaign');
                $('#confirm-message-text').text($('#bulk_message').val() || '');
                $('#confirm-delay-range').text(
                    String($('#delay_min_seconds').val() || '8') + '-' + String($('#delay_max_seconds').val() || '20')
                );
                updateResolvedAttachmentPreview();
            }
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
            $('#selected-count-footer').text(bulkWhatsAppState.selectedLeadIds.size);
            $('#summary-total').text(bulkWhatsAppState.selectedLeadIds.size);
            $('#send-whatsapp').prop('disabled', bulkWhatsAppState.selectedLeadIds.size === 0);
        }

        function setCampaignSummary(summary) {
            $('#summary-ready').text(summary.ready ?? 0);
            $('#summary-sent').text(summary.sent ?? 0);
            $('#summary-failed').text(summary.failed ?? 0);
            $('#preview-pending-count').text(summary.pending ?? 0);
            $('#preview-ready-count').text(summary.ready ?? 0);
            $('#preview-missing-count').text(summary.missing_phone ?? 0);
            $('#campaign-progress-value').text((summary.progress ?? 0) + '%');
            $('#campaign-progress-bar').css('width', (summary.progress ?? 0) + '%');
        }

        function fileToDataUrl(file) {
            return new Promise(function(resolve, reject) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    resolve(String(event.target.result || ''));
                };
                reader.onerror = function() {
                    reject(new Error('Unable to read selected image.'));
                };
                reader.readAsDataURL(file);
            });
        }

        function renderAttachmentPreview(targetSelector, attachment, emptyMessage) {
            if (!attachment || !attachment.url) {
                $(targetSelector).html(
                    '<div class="text-muted f-12 border rounded p-3 bg-light">' + escapeHtml(emptyMessage || 'No image selected.') + '</div>'
                );
                return;
            }

            const sizeText = attachment.size ? ' (' + Math.round((attachment.size / 1024) * 10) / 10 + ' KB)' : '';
            $(targetSelector).html(
                '<div class="border rounded p-2 bg-white">' +
                    '<div class="d-flex align-items-start" style="gap: 0.75rem;">' +
                        '<img src="' + escapeHtml(attachment.url) + '" alt="attachment preview" style="width: 92px; height: 92px; object-fit: cover; border-radius: 12px; border: 1px solid #e2e8f0;">' +
                        '<div class="flex-grow-1">' +
                            '<div class="f-w-600">' + escapeHtml(attachment.name || 'Attachment') + '</div>' +
                            '<div class="bulk-muted f-12 mt-1">' + escapeHtml(attachment.mime || 'image/*') + sizeText + '</div>' +
                            '<div class="bulk-muted f-12 mt-2">This image will be sent with the message caption.</div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );
        }

        function resolveCurrentAttachment() {
            return bulkWhatsAppState.selectedUploadAttachment || bulkWhatsAppState.selectedTemplateAttachment || null;
        }

        function updateResolvedAttachmentPreview() {
            bulkWhatsAppState.currentAttachment = resolveCurrentAttachment();
            renderAttachmentPreview('#bulk-attachment-preview', bulkWhatsAppState.currentAttachment, 'No image selected. You can upload a photo or use a saved template attachment.');
            renderAttachmentPreview('#preview-attachment-panel', bulkWhatsAppState.currentAttachment, 'Attachment preview will appear here if you upload a photo or choose a template with an image.');

            if (bulkWhatsAppState.currentAttachment) {
                $('#confirm-attachment-text').text(bulkWhatsAppState.currentAttachment.name || 'Attachment selected');
            } else {
                $('#confirm-attachment-text').text('No attachment selected');
            }
        }

        function updateCampaignControls(status) {
            const normalized = String(status || '').toLowerCase();
            bulkWhatsAppState.currentCampaignStatus = normalized;
            $('#campaign-status-text').text('Campaign status: ' + (normalized || 'unknown'));

            $('#pause-campaign').toggleClass('d-none', !['queued', 'running'].includes(normalized));
            $('#resume-campaign').toggleClass('d-none', normalized !== 'paused');
            $('#stop-campaign').toggleClass('d-none', ['completed', 'failed', 'stopped'].includes(normalized));
        }

        function buildFormData() {
            const formData = new FormData();
            const payload = collectPayload();

            payload.lead_ids = selectedLeadIdsPayload();
            payload.delay_min_seconds = $('#delay_min_seconds').val();
            payload.delay_max_seconds = $('#delay_max_seconds').val();

            Object.keys(payload).forEach(function(key) {
                const value = payload[key];
                if (Array.isArray(value)) {
                    value.forEach(function(item) {
                        formData.append(key + '[]', item);
                    });
                    return;
                }

                if (value !== null && value !== undefined) {
                    formData.append(key, value);
                }
            });

            const attachmentInput = document.getElementById('bulk_attachment');
            if (attachmentInput && attachmentInput.files && attachmentInput.files[0]) {
                formData.append('attachment', attachmentInput.files[0]);
            }

            return formData;
        }

        function buildTemplateFormData() {
            const formData = new FormData();
            const name = $('#template-name').val();
            const message = $('#template-message').val();
            formData.append('name', name || '');
            formData.append('message', message || '');

            const attachmentInput = document.getElementById('template-attachment');
            if (attachmentInput && attachmentInput.files && attachmentInput.files[0]) {
                formData.append('attachment', attachmentInput.files[0]);
            }

            return formData;
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
                const contentTypeBadge = item.content_type
                    ? '<span class="badge badge-light ml-1">' + escapeHtml(String(item.content_type).replace(/_/g, ' ')) + '</span>'
                    : '';
                return '<tr>' +
                    '<td><strong>' + escapeHtml(item.lead_name || ('Lead #' + item.lead_id)) + '</strong></td>' +
                    '<td>' + escapeHtml(item.phone || '--') + '</td>' +
                    '<td>' + statusBadge(item.status) + contentTypeBadge + '</td>' +
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
                products_services: $('#filter_products_services').val() || [],
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

        function bindBulkCheckboxHandlers() {
            $('#lead-contact-table .select-table-row').removeAttr('onclick');

            const $selectAll = $('#select-all-table');
            $selectAll
                .removeAttr('onclick')
                .off('change.bulkWhatsApp')
                .on('change.bulkWhatsApp', function() {
                    window.selectAllTable(this);
                });
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

            bulkWhatsAppState.previewRecipients = [];

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
            bulkWhatsAppState.previewRecipients = [];

            if (!source.checked) {
                bulkWhatsAppState.selectedLeadIds.clear();
                updateSelectedCount();
                refreshTableSelection();
                return;
            }

            source.disabled = true;
            $.ajax({
                url: bulkWhatsAppRoutes.filteredLeadIds,
                method: 'POST',
                dataType: 'json',
                data: currentLeadFilters(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    const leadIds = response && response.data ? response.data.lead_ids : [];
                    bulkWhatsAppState.selectedLeadIds = new Set((leadIds || []).map(function(id) {
                        return parseInt(id, 10);
                    }).filter(function(id) {
                        return !Number.isNaN(id);
                    }));
                    updateSelectedCount();
                    refreshTableSelection();
                },
                error: function() {
                    source.checked = false;
                    showBulkAlert('Unable to select the filtered leads. Please try again.', 'warning');
                },
                complete: function() {
                    source.disabled = false;
                }
            });
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
                updateCampaignControls(data.campaign.status);

                if (data.campaign.delay_min_seconds || data.campaign.delay_max_seconds) {
                    $('#confirm-delay-range').text(
                        String(data.campaign.delay_min_seconds || $('#delay_min_seconds').val() || '8') + '-' +
                        String(data.campaign.delay_max_seconds || $('#delay_max_seconds').val() || '20')
                    );
                }

                if (data.campaign.attachment) {
                    bulkWhatsAppState.currentAttachment = data.campaign.attachment;
                    updateResolvedAttachmentPreview();
                }
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

                        if (['completed', 'failed', 'stopped', 'paused'].includes(status)) {
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

        function submitCampaign(endpoint, buttonSelector, onSuccess) {
            const selectedIds = selectedLeadIdsPayload();
            if (selectedIds.length === 0) {
                showBulkAlert('Please select at least one lead/contact.', 'warning');
                return;
            }

            const payload = buildFormData();

            const $button = $(buttonSelector);
            $button.prop('disabled', true);

            $.ajax({
                url: endpoint,
                method: 'POST',
                dataType: 'json',
                data: payload,
                processData: false,
                contentType: false,
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

                    if (typeof onSuccess === 'function') {
                        onSuccess(response.data || {});
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
            bindBulkCheckboxHandlers();
            refreshTableSelection();
        });

        $('.bulk-step').on('click', function() {
            const stepNumber = parseInt($(this).data('step'), 10);
            const canOpenStep = stepNumber <= 2
                || (stepNumber === 3 && bulkWhatsAppState.previewRecipients.length > 0)
                || (stepNumber === 4 && bulkWhatsAppState.previewRecipients.length > 0)
                || (stepNumber === 5 && bulkWhatsAppState.currentCampaignId);

            if (stepNumber <= bulkWhatsAppState.currentStep && canOpenStep) {
                showWizardStep(stepNumber);
            }
        });

        function currentLeadFilters() {
            const dateRangePicker = $('#datatableRange').data('daterangepicker');
            let startDate = $('#datatableRange').val();
            let endDate = null;

            if (startDate === '') {
                startDate = null;
            } else if (dateRangePicker) {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            return {
                startDate: startDate,
                endDate: endDate,
                searchText: $('#search-text-field').val(),
                type: $('#type').val(),
                category_id: $('#filter_category_id').val(),
                source_id: $('#filter_source_id').val(),
                status_id: $('#filter_status_id').val(),
                interest_level: $('#filter_interest_level').val(),
                products_services: $('#filter_products_services').val() || [],
                date_filter_on: $('#date_filter_on').val(),
                filter_addedBy: $('#filter_addedBy').val(),
                filter_assignedTo: $('#filter_assigned_to').val()
            };
        }

        $('#lead-contact-table').on('preXhr.dt', function(e, settings, data) {
            Object.assign(data, currentLeadFilters());
        });

        function showTable() {
            window.LaravelDataTables['lead-contact-table'].draw(false);
        }

        $('#type, #filter_assigned_to, #filter_category_id, #filter_status_id, #filter_interest_level, #filter_source_id, #filter_products_services, #date_filter_on, #filter_addedBy')
            .on('change keyup', function() {
                bulkWhatsAppState.selectedLeadIds.clear();
                bulkWhatsAppState.previewRecipients = [];
                updateSelectedCount();
                showTable();
                $('#reset-filters').removeClass('d-none');
            });

        $('#search-text-field').on('keyup', function() {
            bulkWhatsAppState.selectedLeadIds.clear();
            bulkWhatsAppState.previewRecipients = [];
            updateSelectedCount();
            showTable();
            $('#reset-filters').removeClass('d-none');
        });

        $('#datatableRange').on('change', function() {
            bulkWhatsAppState.selectedLeadIds.clear();
            bulkWhatsAppState.previewRecipients = [];
            updateSelectedCount();
            showTable();
            $('#reset-filters').removeClass('d-none');
        });

        $('#reset-filters').on('click', function() {
            $('#type').val('lead');
            $('#filter_category_id').val('all');
            $('#filter_status_id').val('all');
            $('#filter_interest_level').val('all');
            $('#filter_source_id').val('all');
            $('#filter_products_services').val([]);
            $('#filter_addedBy').val('all');
            $('#filter_assigned_to').val('all');
            $('#date_filter_on').val('created_at');
            $('#search-text-field').val('');
            $('#datatableRange').val('');
            bulkWhatsAppState.selectedLeadIds.clear();
            bulkWhatsAppState.previewRecipients = [];
            updateSelectedCount();
            $('.filter-box .select-picker').selectpicker('refresh');
            showTable();
            $(this).addClass('d-none');
        });

        $('#preview-whatsapp').on('click', function() {
            submitCampaign(bulkWhatsAppRoutes.preview, '#preview-whatsapp', function() {
                showWizardStep(3);
            });
        });

        $('#step-1-next').on('click', function() {
            if (bulkWhatsAppState.selectedLeadIds.size === 0) {
                showBulkAlert('Please select at least one lead/contact before continuing.', 'warning');
                return;
            }

            showWizardStep(2);
        });

        $('#step-2-back').on('click', function() {
            showWizardStep(1);
        });

        $('#step-3-back').on('click', function() {
            showWizardStep(2);
        });

        $('#step-3-next').on('click', function() {
            if (bulkWhatsAppState.previewRecipients.length === 0) {
                showBulkAlert('Please generate the preview before continuing.', 'warning');
                return;
            }

            showWizardStep(4);
        });

        $('#step-4-back').on('click', function() {
            showWizardStep(3);
        });

        $('#send-whatsapp').on('click', function() {
            if (bulkWhatsAppState.previewRecipients.length === 0) {
                showBulkAlert('Please generate the preview before sending.', 'warning');
                return;
            }

            submitCampaign(bulkWhatsAppRoutes.send, '#send-whatsapp', function() {
                showWizardStep(5);
            });
        });

        $('#pause-campaign').on('click', function() {
            if (!bulkWhatsAppState.currentCampaignId) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);
            $.ajax({
                url: bulkRoute(bulkWhatsAppRoutes.pause, bulkWhatsAppState.currentCampaignId),
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        updateCampaignFromResponse(response.data);
                        clearInterval(bulkWhatsAppState.pollingHandle);
                        bulkWhatsAppState.pollingHandle = null;
                        showBulkAlert(response.message || 'Campaign paused.', 'success');
                    } else {
                        showBulkAlert(response.message || 'Unable to pause campaign.', 'warning');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to pause campaign.';
                    showBulkAlert(message, 'warning');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        $('#resume-campaign').on('click', function() {
            if (!bulkWhatsAppState.currentCampaignId) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);
            $.ajax({
                url: bulkRoute(bulkWhatsAppRoutes.resume, bulkWhatsAppState.currentCampaignId),
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        updateCampaignFromResponse(response.data);
                        if (response.data.campaign && response.data.campaign.id) {
                            startCampaignPolling(response.data.campaign.id);
                        }
                        showBulkAlert(response.message || 'Campaign resumed.', 'success');
                    } else {
                        showBulkAlert(response.message || 'Unable to resume campaign.', 'warning');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to resume campaign.';
                    showBulkAlert(message, 'warning');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        $('#stop-campaign').on('click', function() {
            if (!bulkWhatsAppState.currentCampaignId) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true);
            $.ajax({
                url: bulkRoute(bulkWhatsAppRoutes.stop, bulkWhatsAppState.currentCampaignId),
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        updateCampaignFromResponse(response.data);
                        clearInterval(bulkWhatsAppState.pollingHandle);
                        bulkWhatsAppState.pollingHandle = null;
                        showBulkAlert(response.message || 'Campaign stopped.', 'success');
                    } else {
                        showBulkAlert(response.message || 'Unable to stop campaign.', 'warning');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Unable to stop campaign.';
                    showBulkAlert(message, 'warning');
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        });

        $('#clear-selection').on('click', function() {
            bulkWhatsAppState.selectedLeadIds.clear();
            updateSelectedCount();
            refreshTableSelection();
            renderPreviewTable([]);
            renderLogTable([]);
        });

        $('#new-campaign').on('click', function() {
            clearInterval(bulkWhatsAppState.pollingHandle);
            bulkWhatsAppState.pollingHandle = null;
            bulkWhatsAppState.currentCampaignId = null;
            bulkWhatsAppState.currentCampaignStatus = null;
            bulkWhatsAppState.previewRecipients = [];
            bulkWhatsAppState.selectedLeadIds.clear();
            bulkWhatsAppState.selectedUploadAttachment = null;
            bulkWhatsAppState.selectedTemplateAttachment = null;
            bulkWhatsAppState.currentAttachment = null;
            $('#template_id').val('').selectpicker('refresh');
            $('#campaign_name').val('');
            $('#bulk_message').val(@json($messagePlaceholder));
            $('#delay_min_seconds').val(8);
            $('#delay_max_seconds').val(20);
            $('#bulk_attachment').val('');
            $('#template-attachment').val('');
            $('#message-counter').text(String($('#bulk_message').val()).length);
            setCampaignSummary({ready: 0, sent: 0, failed: 0, missing_phone: 0, progress: 0});
            updateSelectedCount();
            updateCampaignControls('');
            updateResolvedAttachmentPreview();
            renderPreviewTable([]);
            renderLogTable([]);
            refreshTableSelection();
            showWizardStep(1);
        });

        $('#bulk_message').on('input', function() {
            $('#message-counter').text(this.value.length);
            if (bulkWhatsAppState.currentStep >= 3) {
                bulkWhatsAppState.previewRecipients = [];
                renderPreviewTable([]);
            }
            updateResolvedAttachmentPreview();
        }).trigger('input');

        $('#template_id').on('changed.bs.select change', function() {
            const selected = $(this).find('option:selected');
            const templateMessage = selected.data('message');
            const attachmentUrl = selected.data('attachment-url');
            const attachmentName = selected.data('attachment-name');
            const attachmentMime = selected.data('attachment-mime');
            const attachmentSize = selected.data('attachment-size');

            if (templateMessage !== undefined) {
                $('#bulk_message').val(templateMessage || '');
                $('#message-counter').text(String(templateMessage || '').length);
            }

            if (attachmentUrl) {
                bulkWhatsAppState.selectedTemplateAttachment = {
                    url: String(attachmentUrl),
                    name: attachmentName || 'Template attachment',
                    mime: attachmentMime || 'image/*',
                    size: attachmentSize ? parseInt(attachmentSize, 10) : null
                };
            } else {
                bulkWhatsAppState.selectedTemplateAttachment = null;
            }

            bulkWhatsAppState.selectedUploadAttachment = null;
            $('#bulk_attachment').val('');
            updateResolvedAttachmentPreview();

            if (bulkWhatsAppState.currentStep >= 3) {
                bulkWhatsAppState.previewRecipients = [];
                renderPreviewTable([]);
            }
        });

        $('#bulk_attachment').on('change', async function() {
            const file = this.files && this.files[0] ? this.files[0] : null;

            if (!file) {
                bulkWhatsAppState.selectedUploadAttachment = null;
                updateResolvedAttachmentPreview();
                return;
            }

            try {
                const dataUrl = await fileToDataUrl(file);
                bulkWhatsAppState.selectedUploadAttachment = {
                    url: dataUrl,
                    name: file.name,
                    mime: file.type || 'image/*',
                    size: file.size || null
                };
            } catch (error) {
                bulkWhatsAppState.selectedUploadAttachment = null;
                showBulkAlert(error.message || 'Unable to read selected image.', 'warning');
            }

            updateResolvedAttachmentPreview();

            if (bulkWhatsAppState.currentStep >= 3) {
                bulkWhatsAppState.previewRecipients = [];
                renderPreviewTable([]);
            }
        });

        $('#template-attachment').on('change', async function() {
            const file = this.files && this.files[0] ? this.files[0] : null;

            if (!file) {
                renderAttachmentPreview('#template-attachment-preview', null, 'No template image selected.');
                return;
            }

            try {
                const dataUrl = await fileToDataUrl(file);
                renderAttachmentPreview('#template-attachment-preview', {
                    url: dataUrl,
                    name: file.name,
                    mime: file.type || 'image/*',
                    size: file.size || null
                }, 'No template image selected.');
            } catch (error) {
                showBulkAlert(error.message || 'Unable to read selected template image.', 'warning');
            }
        });

        $('#campaign_name, #delay_min_seconds, #delay_max_seconds').on('input change', function() {
            if (bulkWhatsAppState.currentStep >= 3) {
                bulkWhatsAppState.previewRecipients = [];
                renderPreviewTable([]);
            }
        });

        $('#delay_min_seconds, #delay_max_seconds').on('change', function() {
            const minDelay = parseInt($('#delay_min_seconds').val(), 10) || 8;
            const maxDelay = parseInt($('#delay_max_seconds').val(), 10) || 20;

            if (maxDelay < minDelay) {
                $('#delay_max_seconds').val(minDelay);
            }

            $('#confirm-delay-range').text(String($('#delay_min_seconds').val() || '8') + '-' + String($('#delay_max_seconds').val() || '20'));
        });

        $('#bulk_message, #campaign_name').on('input change', function() {
            if (bulkWhatsAppState.currentStep >= 3) {
                bulkWhatsAppState.previewRecipients = [];
                renderPreviewTable([]);
            }
        });

        $('#open-template-modal').on('click', function() {
            $('#template-name').val('');
            $('#template-message').val($('#bulk_message').val());
            $('#template-attachment').val('');
            renderAttachmentPreview('#template-attachment-preview', null, 'No template image selected.');
            $('#template-modal').modal('show');
        });

        $('#save-template').on('click', function() {
            const name = $('#template-name').val();
            const message = $('#template-message').val();

            if (!name) {
                showBulkAlert('Template name is required.', 'warning');
                return;
            }

            $.ajax({
                url: bulkWhatsAppRoutes.templatesStore,
                method: 'POST',
                dataType: 'json',
                data: buildTemplateFormData(),
                processData: false,
                contentType: false,
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
                        option.dataset.attachmentUrl = template.attachment_url || '';
                        option.dataset.attachmentName = template.attachment_name || '';
                        option.dataset.attachmentMime = template.attachment_mime || '';
                        option.dataset.attachmentSize = template.attachment_size || '';
                        $('#template_id').append(option).selectpicker('refresh').val(String(template.id)).selectpicker('refresh');
                        $('#bulk_message').val(template.message);
                        $('#message-counter').text(String(template.message).length);
                        bulkWhatsAppState.selectedTemplateAttachment = template.attachment_url ? {
                            url: template.attachment_url,
                            name: template.attachment_name || 'Template attachment',
                            mime: template.attachment_mime || 'image/*',
                            size: template.attachment_size || null
                        } : null;
                        bulkWhatsAppState.selectedUploadAttachment = null;
                        $('#bulk_attachment').val('');
                        updateResolvedAttachmentPreview();
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
        bindBulkCheckboxHandlers();
        refreshTableSelection();
        updateResolvedAttachmentPreview();
        updateCampaignControls('');
        showWizardStep(1);
    </script>
@endpush
