<style>
    .lead-profile-shell {
        --crm-bg: #f5f7fc;
        --crm-card: #ffffff;
        --crm-text: #1d2b43;
        --crm-muted: #6c7b96;
        --crm-border: #dbe4f1;
        --crm-primary: #2c6ff3;
        --crm-shadow: 0 8px 24px rgba(22, 44, 87, 0.08);
    }

    .lead-profile-shell .lead-hero {
        border-radius: 18px;
        background: linear-gradient(135deg, #eef4ff 0%, #f5f9ff 55%, #ffffff 100%);
        border: 1px solid var(--crm-border);
        padding: 20px;
        box-shadow: var(--crm-shadow);
    }

    .lead-profile-shell .lead-hero-top {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
    }

    .lead-profile-shell .lead-identity {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .lead-profile-shell .lead-avatar {
        width: 64px;
        height: 64px;
        min-width: 64px;
        border-radius: 50%;
        background: linear-gradient(145deg, #2c6ff3, #37b0f7);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        font-weight: 700;
        text-transform: uppercase;
        box-shadow: 0 8px 18px rgba(44, 111, 243, 0.35);
    }

    .lead-profile-shell .lead-name {
        margin: 0;
        color: var(--crm-text);
        font-size: 24px;
        line-height: 1.2;
        font-weight: 700;
    }

    .lead-profile-shell .lead-company {
        margin: 3px 0;
        color: var(--crm-muted);
        font-size: 14px;
    }

    .lead-profile-shell .lead-phone {
        margin: 0;
        color: #364864;
        font-size: 14px;
        font-weight: 600;
    }

    .lead-profile-shell .lead-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .lead-profile-shell .lead-save-meta {
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .lead-profile-shell .lead-action-row {
        margin-top: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .lead-profile-shell .lead-action-tile {
        width: 74px;
        text-align: center;
        color: var(--crm-text);
        text-decoration: none;
    }

    .lead-profile-shell .lead-action-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 7px 16px rgba(24, 39, 75, 0.2);
        transition: transform 0.15s ease;
    }

    .lead-profile-shell .lead-action-tile:hover .lead-action-icon {
        transform: translateY(-2px);
    }

    .lead-profile-shell .lead-action-label {
        margin-top: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #51627f;
    }

    .lead-profile-shell .lead-card {
        background: var(--crm-card);
        border: 1px solid var(--crm-border);
        border-radius: 16px;
        box-shadow: var(--crm-shadow);
        padding: 18px;
        height: 100%;
    }

    .lead-profile-shell .lead-card-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--crm-text);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lead-profile-shell .lead-profile-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 13px;
    }

    .lead-profile-shell .lead-profile-row:last-child {
        margin-bottom: 0;
    }

    .lead-profile-shell .lead-inline-select-group {
        display: flex;
        align-items: stretch;
        gap: 6px;
        width: 100%;
    }

    .lead-profile-shell .lead-inline-select-group .bootstrap-select,
    .lead-profile-shell .lead-inline-select-group > select {
        flex: 1;
        min-width: 0;
    }

    .lead-profile-shell .btn-inline-add {
        width: 34px;
        min-width: 34px;
        height: 38px;
        padding: 0;
        border-radius: 10px;
        border: 1px solid #cfdcf0;
        background: #f7faff;
        color: #2b5db9;
        font-weight: 700;
    }

    .lead-profile-shell .lead-label {
        width: 190px;
        color: #7b8ba4;
        font-size: 13px;
        font-weight: 600;
        margin: 0;
        padding-top: 7px;
    }

    .lead-profile-shell .lead-value-wrap {
        flex: 1;
        min-width: 200px;
    }

    .lead-profile-shell .js-lead-inline-field,
    .lead-profile-shell .bootstrap-select .dropdown-toggle,
    .lead-profile-shell textarea.form-control,
    .lead-profile-shell input.form-control {
        border-radius: 10px;
        border-color: #d4deec;
        transition: all 0.15s ease;
    }

    .lead-profile-shell .js-lead-inline-field:hover,
    .lead-profile-shell .bootstrap-select .dropdown-toggle:hover,
    .lead-profile-shell textarea.form-control:hover,
    .lead-profile-shell input.form-control:hover {
        border-color: #9fb7df;
    }

    .lead-profile-shell .js-lead-inline-field:focus,
    .lead-profile-shell textarea.form-control:focus,
    .lead-profile-shell input.form-control:focus,
    .lead-profile-shell .bootstrap-select .dropdown-toggle:focus {
        border-color: var(--crm-primary);
        box-shadow: 0 0 0 0.2rem rgba(44, 111, 243, 0.14);
    }

    .lead-profile-shell .lead-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .lead-profile-shell .status-pending {
        background: #fff2cc;
        color: #9a6a00;
    }

    .lead-profile-shell .status-connected {
        background: #dcf6e8;
        color: #0f7a48;
    }

    .lead-profile-shell .status-not-connected {
        background: #fde1e4;
        color: #b02a3c;
    }

    .lead-profile-shell .timeline-item {
        display: flex;
        gap: 10px;
        margin-bottom: 12px;
    }

    .lead-profile-shell .timeline-item:last-child {
        margin-bottom: 0;
    }

    .lead-profile-shell .timeline-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #2c6ff3;
        margin-top: 6px;
    }

    .lead-profile-shell .timeline-title {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #2b3b57;
    }

    .lead-profile-shell .timeline-meta {
        margin: 2px 0 0;
        font-size: 12px;
        color: #6f809d;
    }


    @media (max-width: 768px) {
        .lead-profile-shell .col-12,
        .lead-profile-shell .col-xl-6 {
            padding-left: 8px;
            padding-right: 8px;
        }

        .lead-profile-shell .mb-4 {
            margin-bottom: 10px !important;
        }

        .lead-profile-shell .lead-hero {
            border-radius: 14px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(20, 38, 74, 0.08);
        }

        .lead-profile-shell .lead-hero-top {
            gap: 10px;
        }

        .lead-profile-shell .lead-identity {
            gap: 10px;
        }

        .lead-profile-shell .lead-avatar {
            width: 48px;
            height: 48px;
            min-width: 48px;
            font-size: 22px;
            box-shadow: 0 2px 8px rgba(44, 111, 243, 0.25);
        }

        .lead-profile-shell .lead-name {
            font-size: 15px;
            line-height: 1.15;
        }

        .lead-profile-shell .lead-company {
            margin: 2px 0;
            font-size: 11px;
        }

        .lead-profile-shell .lead-phone {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
            font-size: 12px;
            overflow: hidden;
        }

        .lead-profile-shell .lead-status-badge {
            padding: 3px 8px;
            font-size: 11px;
            line-height: 1.2;
            margin-left: 0 !important;
        }

        .lead-profile-shell .lead-header-actions {
            justify-content: flex-start;
            width: 100%;
            gap: 6px;
        }

        .lead-profile-shell .lead-header-actions .convert-lead-to-client {
            width: 100%;
            font-size: 13px;
            line-height: 1.2;
            padding: 8px 10px;
            min-height: 36px;
        }

        .lead-profile-shell .lead-save-meta {
            margin-top: 8px;
        }

        .lead-profile-shell .lead-save-meta small {
            font-size: 12px;
            line-height: 1.2;
        }

        .lead-profile-shell .lead-action-row {
            margin-top: 8px;
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 6px;
            padding-bottom: 2px;
        }

        .lead-profile-shell .lead-action-tile {
            width: 52px;
            min-width: 52px;
            flex: 0 0 52px;
        }

        .lead-profile-shell .lead-action-icon {
            width: 34px;
            height: 34px;
            font-size: 13px;
            box-shadow: 0 2px 6px rgba(24, 39, 75, 0.14);
            transition: none;
        }

        .lead-profile-shell .lead-action-tile:hover .lead-action-icon {
            transform: none;
        }

        .lead-profile-shell .lead-action-label {
            margin-top: 3px;
            font-size: 10px;
            line-height: 1.2;
        }

        .lead-profile-shell .lead-card {
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 2px 8px rgba(20, 38, 74, 0.07);
        }

        .lead-profile-shell .lead-card-title {
            font-size: 14px;
            margin-bottom: 10px;
            gap: 6px;
        }

        .lead-profile-shell .lead-profile-row {
            display: grid;
            grid-template-columns: 110px minmax(0, 1fr);
            gap: 6px 8px;
            align-items: center;
            margin-bottom: 8px;
        }

        .lead-profile-shell .lead-label {
            width: auto;
            padding-top: 0;
            font-size: 12px;
            line-height: 1.25;
        }

        .lead-profile-shell .lead-value-wrap {
            min-width: 0;
            width: 100%;
        }

        .lead-profile-shell .lead-value-wrap .form-control,
        .lead-profile-shell .lead-value-wrap .bootstrap-select .dropdown-toggle {
            height: 34px;
            min-height: 34px;
            padding-top: 5px;
            padding-bottom: 5px;
            font-size: 13px;
            border-radius: 8px;
        }

        .lead-profile-shell .lead-value-wrap textarea.form-control {
            height: auto;
            min-height: 58px;
        }

        .lead-profile-shell #profile_mobile_country_code {
            width: 68px !important;
            min-width: 68px !important;
            max-width: 68px !important;
            padding-left: 6px;
            padding-right: 4px;
        }

        .lead-profile-shell .btn-inline-add {
            width: 30px;
            min-width: 30px;
            height: 34px;
            border-radius: 8px;
        }

        .lead-profile-shell .timeline-item {
            gap: 8px;
            margin-bottom: 8px;
        }

        .lead-profile-shell .timeline-title {
            font-size: 12px;
        }

        .lead-profile-shell .timeline-meta {
            font-size: 11px;
        }

    }
</style>

<div class="row lead-profile-shell">
    <div class="col-12">
        @php
            $contactNumber = $leadContact->mobile ?: ($leadContact->cell ?: $leadContact->office);
            $sanitizedPhone = $contactNumber ? preg_replace('/\D+/', '', $contactNumber) : null;
            $whatsAppUrl = $sanitizedPhone ? 'https://wa.me/' . $sanitizedPhone : null;
            $mailUrl = $leadContact->client_email ? 'mailto:' . $leadContact->client_email : null;
            $callUrl = $contactNumber ? 'tel:' . $contactNumber : null;
            $directMessageUrl = (!is_null($leadContact->client_id) && in_array('messages', user_modules()))
                ? route('messages.index') . '?user=' . $leadContact->client_id
                : null;
            $viewLeadCategoryPermission = user()->permission('view_lead_category');
            $viewLeadSourcesPermission = user()->permission('view_lead_sources');
            $addLeadSourcesPermission = user()->permission('add_lead_sources');
            $addLeadCategoryPermission = user()->permission('add_lead_category');
            $canInlineQuickEdit = (bool) ($canInlineEdit ?? false);
            $quickUpdateUrl = route('lead-contact.quick_update', $leadContact->id);
            $leadSourceCreateUrl = ($addLeadSourcesPermission === 'all' || $addLeadSourcesPermission === 'added') ? route('lead-contact.quick_add_form', 'source') : null;
            $leadCategoryCreateUrl = ($addLeadCategoryPermission === 'all' || $addLeadCategoryPermission === 'added') ? route('lead-contact.quick_add_form', 'category') : null;
            $leadStatusCreateUrl = in_array('admin', user_roles()) ? route('lead-contact.quick_add_form', 'status') : null;
            $addProductPermission = user()->permission('add_product');
            $leadProductCreateUrl = in_array('products', user_modules()) && in_array($addProductPermission, ['all', 'added'])
                ? route('lead-contact.quick_add_form', 'product')
                : null;
            $rawProfileMobile = preg_replace('/\D+/', '', (string) ($leadContact->mobile ?? ''));
            $profileMobileLocal = (str_starts_with($rawProfileMobile, '91') && strlen($rawProfileMobile) === 12) ? substr($rawProfileMobile, 2) : substr($rawProfileMobile, -10);
            $selectedProductServices = collect(preg_split('/[\r\n,]+/', (string) ($leadContact->products_services ?? '')))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->unique()
                ->values();
            $leadInitial = strtoupper(substr(trim((string) ($leadContact->client_name ?: 'L')), 0, 1));
            $statusKey = $leadContact->contact_status ?: 'pending';
            $statusLabel = str($statusKey)->replace('_', ' ')->title();
            $statusClass = $statusKey === 'connected' ? 'status-connected' : ($statusKey === 'not_connected' ? 'status-not-connected' : 'status-pending');
            $noteCreateUrl = route('lead-notes.create') . '?lead=' . $leadContact->id;
            $followUpCreateUrl = route('lead-contact.follow_up', $leadContact->id);
            $taskCreateUrl = route('tasks.create') . '?lead_id=' . $leadContact->id;
            $timelineCreated = $leadContact->created_at
                ? $leadContact->created_at->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format)
                : '--';
            $timelineUpdated = $leadContact->updated_at
                ? $leadContact->updated_at->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format)
                : '--';
            $greetingStatus = $leadContact->whatsapp_greeting_status;
            $greetingStatusLabel = $greetingStatus === 'sent'
                ? 'Sent'
                : ($greetingStatus === 'failed' ? 'Failed' : 'Pending');
            $greetingStatusClass = $greetingStatus === 'sent'
                ? 'text-success'
                : ($greetingStatus === 'failed' ? 'text-danger' : 'text-warning');
            $greetingSentAt = $leadContact->whatsapp_greeting_sent_at
                ? $leadContact->whatsapp_greeting_sent_at->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format)
                : '--';
        @endphp

        <div class="lead-hero mb-4">
            <div class="lead-hero-top">
                <div class="lead-identity">
                    <div class="lead-avatar">{{ $leadInitial }}</div>
                    <div>
                        <h3 class="lead-name">{{ $leadContact->client_name ?: '--' }}</h3>
                        <p class="lead-company">{{ $leadContact->company_name ?: 'No company assigned' }}</p>
                        <p class="lead-phone mb-0">
                            <i class="fa fa-phone mr-1"></i>{{ $contactNumber ?: '--' }}
                            <span class="lead-status-badge {{ $statusClass }} ml-2" id="lead-contact-status-badge">{{ $statusLabel }}</span>
                        </p>
                    </div>
                </div>
                <div class="lead-header-actions">
                    @if (!$leadContact->client_id)
                        <button type="button" class="btn btn-primary convert-lead-to-client" data-url="{{ route('lead-contact.convert_to_client', $leadContact->id) }}">
                            <i class="fa fa-user-check mr-1"></i>@lang('modules.lead.changeToClient')
                        </button>
                    @endif
                </div>
            </div>
            @if ($canInlineQuickEdit)
                <div class="lead-save-meta">
                    <small class="text-muted" id="inline-save-summary">Auto-save enabled for all fields</small>
                </div>
            @endif

            <div class="lead-action-row">
                @if ($callUrl)
                    <a class="lead-action-tile" href="{{ $callUrl }}">
                        <span class="lead-action-icon" style="background:#1f6feb;"><i class="fa fa-phone"></i></span>
                        <div class="lead-action-label">Call</div>
                    </a>
                @else
                    <span class="lead-action-tile opacity-50">
                        <span class="lead-action-icon" style="background:#8ca0c2;"><i class="fa fa-phone"></i></span>
                        <div class="lead-action-label">Call</div>
                    </span>
                @endif

                @if ($whatsAppUrl)
                    <a class="lead-action-tile" href="{{ $whatsAppUrl }}" target="_blank">
                        <span class="lead-action-icon" style="background:#1faa59;"><i class="fa fa-whatsapp"></i></span>
                        <div class="lead-action-label">WhatsApp</div>
                    </a>
                @else
                    <span class="lead-action-tile opacity-50">
                        <span class="lead-action-icon" style="background:#8ca0c2;"><i class="fa fa-whatsapp"></i></span>
                        <div class="lead-action-label">WhatsApp</div>
                    </span>
                @endif

                @if ($mailUrl)
                    <a class="lead-action-tile" href="{{ $mailUrl }}">
                        <span class="lead-action-icon" style="background:#ef4c74;"><i class="fa fa-envelope"></i></span>
                        <div class="lead-action-label">Email</div>
                    </a>
                @else
                    <span class="lead-action-tile opacity-50">
                        <span class="lead-action-icon" style="background:#8ca0c2;"><i class="fa fa-envelope"></i></span>
                        <div class="lead-action-label">Email</div>
                    </span>
                @endif

                <a class="lead-action-tile openRightModal" href="{{ $noteCreateUrl }}">
                    <span class="lead-action-icon" style="background:#8b5cf6;"><i class="fa fa-sticky-note"></i></span>
                    <div class="lead-action-label">Add Note</div>
                </a>

                <a class="lead-action-tile js-open-lead-popup" href="javascript:;" data-url="{{ $followUpCreateUrl }}">
                    <span class="lead-action-icon" style="background:#0ea5a8;"><i class="fa fa-calendar-plus"></i></span>
                    <div class="lead-action-label">Add Followup</div>
                </a>

                <a class="lead-action-tile openRightModal" href="{{ $taskCreateUrl }}">
                    <span class="lead-action-icon" style="background:#f59e0b;"><i class="fa fa-tasks"></i></span>
                    <div class="lead-action-label">Create Task</div>
                </a>

                @if ($directMessageUrl)
                    <a class="lead-action-tile" href="{{ $directMessageUrl }}">
                        <span class="lead-action-icon" style="background:#5761ff;"><i class="fa fa-comments"></i></span>
                        <div class="lead-action-label">Messages</div>
                    </a>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-xl-6 mb-4">
                <div class="lead-card">
                    <div class="lead-card-title"><i class="fa fa-chart-line text-primary"></i>Qualifiers</div>

                    @if ($canInlineQuickEdit && $viewLeadSourcesPermission !== 'none')
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.source')</p>
                            <div class="lead-value-wrap w-100">
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select js-inline-autosave" data-live-search="true"
                                        data-field="source_id" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->source_id ?? '' }}">
                                        <option value="">--</option>
                                        @foreach ($sources as $source)
                                            <option value="{{ $source->id }}" @selected($leadContact->source_id == $source->id)>{{ $source->type }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn-inline-add js-inline-create-option"
                                        data-create-url="{{ $leadSourceCreateUrl }}"
                                        title="Add source">+</button>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.source')</p>
                            <div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->leadSource ? $leadContact->leadSource->type : '--' }}</p></div>
                        </div>
                    @endif

                    @if ($canInlineQuickEdit && $viewLeadCategoryPermission !== 'none')
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.leadCategory')</p>
                            <div class="lead-value-wrap w-100">
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select js-inline-autosave" data-live-search="true"
                                        data-field="category_id" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->category_id ?? '' }}">
                                        <option value="">--</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($leadContact->category_id == $category->id)>{{ $category->category_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn-inline-add js-inline-create-option"
                                        data-create-url="{{ $leadCategoryCreateUrl }}"
                                        title="Add category">+</button>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.leadCategory')</p>
                            <div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->category->category_name ?? '--' }}</p></div>
                        </div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">Lead Status</p>
                            <div class="lead-value-wrap w-100">
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select js-inline-autosave" data-live-search="true"
                                        data-container="body"
                                        data-field="status_id" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->status_id ?? '' }}">
                                        <option value="">--</option>
                                        @foreach ($statuses as $statusItem)
                                            <option value="{{ $statusItem->id }}" @selected($leadContact->status_id == $statusItem->id)
                                                data-content="<span><i class='fa fa-circle mr-2' style='color: {{ $statusItem->label_color }}'></i>{{ $statusItem->type }}</span>">
                                                {{ $statusItem->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn-inline-add js-inline-create-option"
                                        data-create-url="{{ $leadStatusCreateUrl }}"
                                        title="Add status">+</button>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @elseif ($leadContact->leadStatus)
                        <div class="lead-profile-row">
                            <p class="lead-label">Lead Status</p>
                            <div class="lead-value-wrap"><x-status :value="$leadContact->leadStatus->type" :style="'color:' . $leadContact->leadStatus->label_color" /></div>
                        </div>
                    @else
                        <div class="lead-profile-row">
                            <p class="lead-label">Lead Status</p>
                            <div class="lead-value-wrap"><p class="mb-0 text-dark">--</p></div>
                        </div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">Interest Level</p>
                            <div class="lead-value-wrap w-100">
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select js-inline-autosave" data-live-search="true" data-container="body"
                                        data-field="interest_level" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->interest_level ?? '' }}">
                                        <option value="">--</option>
                                        <option value="low" @selected($leadContact->interest_level === 'low')
                                            data-content="<span><i class='fa fa-circle mr-2' style='color:#64748b'></i>Low</span>">Low</option>
                                        <option value="medium" @selected($leadContact->interest_level === 'medium')
                                            data-content="<span><i class='fa fa-circle mr-2' style='color:#2563eb'></i>Medium</span>">Medium</option>
                                        <option value="high" @selected($leadContact->interest_level === 'high')
                                            data-content="<span><i class='fa fa-circle mr-2' style='color:#ea580c'></i>High</span>">High</option>
                                        <option value="very_high" @selected($leadContact->interest_level === 'very_high')
                                            data-content="<span><i class='fa fa-circle mr-2' style='color:#16a34a'></i>Very High</span>">Very High</option>
                                    </select>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row">
                            <p class="lead-label">Interest Level</p>
                            <div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->interest_level ? str($leadContact->interest_level)->replace('_', ' ')->title() : '--' }}</p></div>
                        </div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">Deal Size</p>
                            <div class="lead-value-wrap w-100">
                                <input type="number" min="0" step="0.01" class="form-control js-lead-inline-field js-inline-autosave" data-field="deal_size" data-url="{{ $quickUpdateUrl }}"
                                    data-prev-value="{{ !is_null($leadContact->deal_size) ? $leadContact->deal_size : '' }}" value="{{ !is_null($leadContact->deal_size) ? $leadContact->deal_size : '' }}">
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row">
                            <p class="lead-label">Deal Size</p>
                            <div class="lead-value-wrap"><p class="mb-0 text-dark">{{ !is_null($leadContact->deal_size) ? number_format((float) $leadContact->deal_size, 2) : '--' }}</p></div>
                        </div>
                    @endif

                    <div class="lead-profile-row">
                        <p class="lead-label">Lead Contact Status</p>
                        <div class="lead-value-wrap w-100">
                            @if ($canInlineQuickEdit)
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select" data-live-search="true"
                                        data-field="contact_status" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->contact_status ?? '' }}">
                                        <option value="">--</option>
                                        <option value="pending" @selected($leadContact->contact_status === 'pending')>Pending</option>
                                        <option value="connected" @selected($leadContact->contact_status === 'connected')>Connected</option>
                                        <option value="not_connected" @selected($leadContact->contact_status === 'not_connected')>Not Connected</option>
                                    </select>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            @else
                                <p class="mb-0 text-dark">{{ $leadContact->contact_status ? str($leadContact->contact_status)->replace('_', ' ')->title() : '--' }}</p>
                            @endif
                        </div>
                    </div>

                    @if (!empty($leadContact->contact_status_reason))
                        <div class="lead-profile-row">
                            <p class="lead-label">Not Connected Reason</p>
                            <div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->contact_status_reason }}</p></div>
                        </div>
                    @endif

                    <div class="lead-profile-row">
                        <p class="lead-label">Products / Services</p>
                        <div class="lead-value-wrap w-100">
                            @if ($canInlineQuickEdit)
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select js-inline-autosave"
                                        data-live-search="true" data-field="products_services" data-url="{{ $quickUpdateUrl }}"
                                        data-prev-value="{{ $selectedProductServices->implode(', ') }}" multiple data-size="8"
                                        title="Select products/services">
                                        @foreach (($products ?? collect()) as $productItem)
                                            <option value="{{ $productItem->name }}" @selected($selectedProductServices->contains($productItem->name))>
                                                {{ $productItem->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn-inline-add js-inline-create-option"
                                        data-create-url="{{ $leadProductCreateUrl }}"
                                        title="Add product">+</button>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            @else
                                <p class="mb-0 text-dark">{{ !empty($leadContact->products_services) ? $leadContact->products_services : '--' }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 mb-4">
                <div class="lead-card">
                    <div class="lead-card-title"><i class="fa fa-address-book text-primary"></i>Contact Info</div>

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('app.name')</p>
                            <div class="lead-value-wrap w-100">
                                <input type="text" class="form-control js-lead-inline-field" data-field="client_name" data-url="{{ $quickUpdateUrl }}"
                                    data-prev-value="{{ $leadContact->client_name ?? '' }}" value="{{ $leadContact->client_name ?? '' }}">
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('app.name')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->client_name ?? '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('app.email')</p>
                            <div class="lead-value-wrap w-100">
                                <input type="email" class="form-control js-lead-inline-field" data-field="client_email" data-url="{{ $quickUpdateUrl }}"
                                    data-prev-value="{{ $leadContact->client_email ?? '' }}" value="{{ $leadContact->client_email ?? '' }}">
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('app.email')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->client_email ?? '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.companyName')</p>
                            <div class="lead-value-wrap w-100">
                                <input type="text" class="form-control js-lead-inline-field" data-field="company_name" data-url="{{ $quickUpdateUrl }}"
                                    data-prev-value="{{ $leadContact->company_name ?? '' }}" value="{{ $leadContact->company_name ?? '' }}">
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('modules.lead.companyName')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->company_name ?: '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.website')</p>
                            <div class="lead-value-wrap w-100">
                                <input type="text" class="form-control js-lead-inline-field" data-field="website" data-url="{{ $quickUpdateUrl }}"
                                    data-prev-value="{{ $leadContact->website ?? '' }}" value="{{ $leadContact->website ?? '' }}">
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('modules.lead.website')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->website ?? '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.mobile')</p>
                            <div class="lead-value-wrap w-100">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <select class="form-control" id="profile_mobile_country_code" style="width: 90px; min-width: 90px; max-width: 90px;">
                                            @foreach ($countries as $countryItem)
                                                @php $code = preg_replace('/\D+/', '', (string) $countryItem->phonecode); @endphp
                                                @if (!empty($code))
                                                    <option value="{{ $code }}" data-country="{{ $countryItem->nicename }}" @selected($leadContact->country == $countryItem->nicename)>+{{ $code }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="text" class="form-control js-lead-inline-field" data-field="mobile" data-url="{{ $quickUpdateUrl }}"
                                        maxlength="12" inputmode="numeric" autocomplete="off" data-prev-value="{{ $profileMobileLocal }}" value="{{ $profileMobileLocal }}">
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('modules.lead.mobile')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->mobile ?? '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.client.officePhoneNumber')</p>
                            <div class="lead-value-wrap w-100">
                                <input type="text" class="form-control js-lead-inline-field" data-field="office" data-url="{{ $quickUpdateUrl }}"
                                    data-prev-value="{{ $leadContact->office ?? '' }}" value="{{ $leadContact->office ?? '' }}">
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('modules.client.officePhoneNumber')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->office ?? '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('app.country')</p>
                            <div class="lead-value-wrap w-100">
                                <div class="lead-inline-select-group">
                                    <select class="form-control select-picker js-lead-inline-field js-lead-inline-select" data-live-search="true"
                                        data-field="country" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->country ?? '' }}">
                                        <option value="">--</option>
                                        @foreach ($countries as $countryItem)
                                            <option value="{{ $countryItem->nicename }}" data-phonecode="{{ $countryItem->phonecode }}" @selected($leadContact->country == $countryItem->nicename)>
                                                {{ $countryItem->nicename }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn-inline-add js-inline-create-option"
                                        data-fixed="1" data-message="Country list is managed globally." title="Fixed list">+</button>
                                </div>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('app.country')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->country ?? '--' }}</p></div></div>
                    @endif

                    @if ($canInlineQuickEdit)
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.lead.address')</p>
                            <div class="lead-value-wrap w-100">
                                <textarea class="form-control js-lead-inline-field" rows="3" data-field="address" data-url="{{ $quickUpdateUrl }}" data-prev-value="{{ $leadContact->address ?? '' }}">{{ $leadContact->address ?? '' }}</textarea>
                                <small class="text-muted d-none js-inline-save-state"></small>
                            </div>
                        </div>
                    @else
                        <div class="lead-profile-row"><p class="lead-label">@lang('modules.lead.address')</p><div class="lead-value-wrap"><p class="mb-0 text-dark">{{ $leadContact->address ?? '--' }}</p></div></div>
                    @endif

                    @if(!is_null($leadContact->added_by))
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('app.addedBy')</p>
                            <div class="lead-value-wrap"><x-employee :user="$leadContact->addedBy" /></div>
                        </div>
                    @endif

                    @if(!is_null($leadContact->assigned_to))
                        <div class="lead-profile-row">
                            <p class="lead-label">@lang('modules.tasks.assignTo')</p>
                            <div class="lead-value-wrap"><x-employee :user="$leadContact->assignedTo" /></div>
                        </div>
                    @endif

                    <x-forms.custom-field-show :fields="$fields" :model="$leadContact"></x-forms.custom-field-show>
                </div>
            </div>

            <div class="col-xl-6 mb-4">
                <div class="lead-card">
                    <div class="lead-card-title"><i class="fa fa-sticky-note text-primary"></i>Notes</div>
                    @if (!empty($leadContact->note))
                        <p class="mb-3 text-dark-grey">{{ strip_tags($leadContact->note) }}</p>
                    @else
                        <p class="mb-3 text-muted">No notes added yet.</p>
                    @endif
                    <a href="{{ $noteCreateUrl }}" class="btn btn-outline-primary btn-sm openRightModal"><i class="fa fa-plus mr-1"></i>Add Note</a>
                </div>
            </div>

            <div class="col-xl-6 mb-4">
                <div class="lead-card">
                    <div class="lead-card-title"><i class="fa fa-history text-primary"></i>Activity Timeline</div>
                    <div class="timeline-item">
                        <span class="timeline-dot"></span>
                        <div>
                            <p class="timeline-title">Lead Created</p>
                            <p class="timeline-meta">{{ $timelineCreated }}</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-dot"></span>
                        <div>
                            <p class="timeline-title">Last Updated</p>
                            <p class="timeline-meta">{{ $timelineUpdated }}</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <span class="timeline-dot"></span>
                        <div>
                            <p class="timeline-title">WhatsApp Greeting <span class="{{ $greetingStatusClass }}">{{ $greetingStatusLabel }}</span></p>
                            <p class="timeline-meta">{{ $greetingSentAt }}</p>
                        </div>
                    </div>
                    @if (!empty($leadContact->whatsapp_greeting_error))
                        <p class="mb-0 mt-2 text-danger"><strong>Greeting Error:</strong> {{ $leadContact->whatsapp_greeting_error }}</p>
                    @endif
                    <div class="mt-3">
                        <a href="{{ route('lead-contact.show', $leadContact->id) . '?tab=history' }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-clock mr-1"></i>View History
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    (function() {
        function updateContactStatusBadge() {
            const $field = $('.js-lead-inline-field[data-field="contact_status"]').first();
            const key = (($field.length ? $field.val() : '{{ $statusKey }}') || 'pending').toString();
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
            const $badge = $('#lead-contact-status-badge');
            if (!$badge.length) {
                return;
            }

            $badge.removeClass('status-pending status-connected status-not-connected');
            if (key === 'connected') {
                $badge.addClass('status-connected');
            } else if (key === 'not_connected') {
                $badge.addClass('status-not-connected');
            } else {
                $badge.addClass('status-pending');
            }
            $badge.text(label || 'Pending');
        }

        $('body').off('click.leadPopupOpen').on('click.leadPopupOpen', '.js-open-lead-popup', function() {
            const url = $(this).data('url') || $(this).attr('href');
            if (!url || url === 'javascript:;') {
                return;
            }
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').off('click.leadInlineCreateOption').on('click.leadInlineCreateOption', '.js-inline-create-option', function() {
            const isFixed = Number($(this).data('fixed') || 0) === 1;
            const createUrl = ($(this).data('create-url') || '').toString();
            const message = ($(this).data('message') || '').toString();

            if (isFixed || !createUrl) {
                const infoMessage = message || 'This dropdown has fixed values.';
                if (typeof toastr !== 'undefined') {
                    toastr.info(infoMessage);
                } else {
                    Swal.fire({ icon: 'info', title: 'Info', text: infoMessage });
                }
                return;
            }

            const sameOriginUrl = new URL(createUrl, window.location.origin);
            const $modal = $(MODAL_LG);

            if (sameOriginUrl.origin !== window.location.origin) {
                sameOriginUrl.protocol = window.location.protocol;
                sameOriginUrl.host = window.location.host;
            }

            $modal.find('.modal-content').html(
                '<div class="modal-header"><h5 class="modal-title">Loading...</h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>' +
                '<div class="modal-body">Loading...</div>'
            );
            $modal.modal({ show: true, backdrop: 'static', keyboard: false });

            $.ajax({
                url: sameOriginUrl.pathname + sameOriginUrl.search,
                type: 'GET',
                dataType: 'html',
                timeout: 20000
            }).done(function(html) {
                $modal.find('.modal-content').html(html);
                if (typeof init === 'function') {
                    init(MODAL_LG);
                }
            }).fail(function(xhr) {
                let message = 'Unable to load this form. Please try again.';
                if (xhr.status === 403) {
                    message = 'You do not have permission to add this option.';
                } else if (xhr.status === 404) {
                    message = 'This add form is not available.';
                }

                $modal.find('.modal-body').html('<div class="alert alert-danger mb-0">' + message + '</div>');
            });
        });

        $('body').off('change.leadStatusBadge').on('change.leadStatusBadge', '.js-lead-inline-field[data-field="contact_status"]', function() {
            updateContactStatusBadge();
        });

        updateContactStatusBadge();
    })();
</script>

<script>
    $('body').off('click.convertLead').on('click.convertLead', '.convert-lead-to-client', function() {
        const url = $(this).data('url');
        const archive = $(this).data('archive') ? 1 : 0;

        Swal.fire({
            title: "@lang('modules.lead.changeToClient')",
            text: "This will create a client from the current lead.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: "@lang('modules.lead.changeToClient')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.easyAjax({
                url: url,
                type: 'POST',
                blockUI: true,
                data: {
                    _token: "{{ csrf_token() }}",
                    archive: archive
                },
                success: function(response) {
                    if (response.status === 'success' && response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });
    });

    const leadInlineTimers = {};
    const leadInlinePendingRequests = {};
    const DROPDOWN_AUTO_SAVE_DELAY_MS = 400;
    const TEXT_AUTO_SAVE_DELAY_MS = 700;

    function getProfileSelectedCountry() {
        return ($('.js-lead-inline-field[data-field="country"]').first().val() || 'India').toString();
    }

    function getProfileCodeFromCountry(countryName) {
        const $country = $('.js-lead-inline-field[data-field="country"]').first();
        const $match = $country.find('option').filter(function() {
            return ($(this).val() || '').toString().toLowerCase() === (countryName || '').toString().toLowerCase();
        }).first();

        return (($match.data('phonecode') || '91').toString().replace(/\D+/g, '')) || '91';
    }

    function syncProfileCountryToCode() {
        const countryName = getProfileSelectedCountry();
        const code = getProfileCodeFromCountry(countryName);
        $('#profile_mobile_country_code').val(code);
        return code;
    }

    function syncProfileCodeToCountry() {
        const code = ($('#profile_mobile_country_code').val() || '91').toString().replace(/\D+/g, '') || '91';
        const $country = $('.js-lead-inline-field[data-field="country"]').first();
        const $match = $country.find('option').filter(function() {
            return (($(this).data('phonecode') || '').toString().replace(/\D+/g, '')) === code;
        }).first();

        if ($match.length) {
            $country.val($match.val()).trigger('change');
        }

        return code;
    }

    function getProfileCountryCode() {
        const code = ($('#profile_mobile_country_code').val() || '91').toString().replace(/\D+/g, '');
        return code || '91';
    }

    function updateProfileMobilePrefix() {
        const code = getProfileCountryCode();
        const maxLength = code === '91' ? 10 : 12;
        const $mobile = $('.js-lead-inline-field[data-field="mobile"]').first();
        $mobile.attr('maxlength', maxLength);
        $mobile.val(inlineFieldValue($mobile));
    }

    function inlineFieldValue($field) {
        const fieldName = $field.data('field');

        if (fieldName === 'mobile') {
            const countryCode = getProfileCountryCode();
            const limit = countryCode === '91' ? 10 : 12;
            return ($field.val() || '').toString().replace(/\D+/g, '').slice(0, limit);
        }

        if (fieldName === 'products_services') {
            const values = $field.val() || [];
            if (Array.isArray(values)) {
                return values
                    .map(function(item) { return (item || '').toString().trim(); })
                    .filter(function(item) { return item.length > 0; })
                    .sort()
                    .join(', ');
            }

            return (values || '').toString().trim();
        }

        if ($field.is('select')) {
            return ($field.val() || '').toString();
        }

        return ($field.val() || '').toString().trim();
    }

    function ensureFieldLoader($field) {
        const $wrap = $field.closest('.w-100');
        $wrap.addClass('position-relative');

        if ($wrap.find('.js-inline-loader').length === 0) {
            $wrap.append(
                '<span class="js-inline-loader d-none" style="position:absolute; right:10px; top:50%; transform:translateY(-50%);">' +
                '<span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>' +
                '</span>'
            );
        }
    }

    function showFieldLoader($field, show) {
        ensureFieldLoader($field);
        const $loader = $field.closest('.w-100').find('.js-inline-loader');
        $loader.toggleClass('d-none', !show);
    }

    function setInlineState($field, text, type) {
        const $state = $field.closest('.w-100').find('.js-inline-save-state');

        if (!text) {
            $state.addClass('d-none').text('');
            return;
        }

        $state.removeClass('d-none text-success text-danger text-muted');
        $state.addClass(type || 'text-muted');
        $state.text(text);

        if (type === 'text-success') {
            setTimeout(function() {
                $state.addClass('d-none').text('');
            }, 1400);
        }
    }

    function showRetryToast($field, message) {
        const fieldName = $field.data('field');
        const safeMessage = message || 'Unable to save changes.';

        if (typeof toastr !== 'undefined') {
            toastr.error(
                safeMessage + ' <a href="javascript:;" class="js-inline-retry ml-2" data-field="' + fieldName + '">Retry</a>',
                'Save failed',
                { closeButton: true, timeOut: 8000, extendedTimeOut: 2500, escapeHtml: false }
            );
            return;
        }

        Swal.fire({
            icon: 'error',
            title: 'Save failed',
            text: safeMessage,
            showCancelButton: true,
            confirmButtonText: 'Retry',
            cancelButtonText: 'Dismiss',
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                saveInlineField($field, { force: true });
            }
        });
    }

    function revertInlineField($field) {
        const prev = ($field.attr('data-prev-value') || '').toString();
        const fieldName = ($field.data('field') || '').toString();
        const usingSelectPicker = $field.is('select') && typeof $field.selectpicker === 'function';

        if (usingSelectPicker) {
            if (fieldName === 'products_services') {
                const values = prev
                    .split(',')
                    .map(function(item) { return item.trim(); })
                    .filter(function(item) { return item.length > 0; });
                $field.selectpicker('val', values);
            } else {
                $field.selectpicker('val', prev);
            }
        } else {
            $field.val(prev);
        }
    }

    function saveInlineField($field, options) {
        options = options || {};

        const field = $field.data('field');
        const url = $field.data('url');
        const value = inlineFieldValue($field);
        const previousValue = ($field.attr('data-prev-value') || '').toString();

        if (!options.force && value === previousValue) {
            return $.Deferred().resolve().promise();
        }

        if (field === 'mobile') {
            const countryCode = getProfileCountryCode();
            const valid = countryCode === '91' ? value.length === 10 : (value.length >= 6 && value.length <= 12);

            if (!valid) {
                setInlineState($field, 'Invalid mobile', 'text-danger');
                showRetryToast($field, countryCode === '91'
                    ? 'Please enter a valid 10-digit mobile number for India (+91).'
                    : 'Please enter a valid mobile number (6-12 digits) for selected country code.');
                return $.Deferred().reject().promise();
            }
        }

        const requestData = {
            _token: "{{ csrf_token() }}",
            field: field,
            value: value
        };

        if (field === 'mobile') {
            requestData.country = $('.js-lead-inline-field[data-field="country"]').first().val() || 'India';
        }

        if (field === 'country') {
            requestData.value = ($field.val() || '').toString();
        }

        if (field === 'mobile' && value.length === 0) {
            setInlineState($field, 'Invalid mobile', 'text-danger');
            showRetryToast($field, 'Please enter a mobile number.');
            return $.Deferred().reject().promise();
        }

        if (leadInlinePendingRequests[field] && options.abortPrevious === true) {
            try {
                leadInlinePendingRequests[field].abort();
            } catch (e) {
                // noop
            }
        }

        setInlineState($field, 'Saving...', 'text-muted');
        showFieldLoader($field, true);
        $('#inline-save-summary').removeClass('text-danger text-success').addClass('text-muted').text('Saving changes...');

        const ajaxData = {
            _token: requestData._token,
            field: requestData.field,
            value: requestData.value
        };

        if (typeof requestData.country !== 'undefined') {
            ajaxData.country = requestData.country;
        }

        const request = $.easyAjax({
            url: url,
            type: 'POST',
            disableButton: true,
            blockUI: false,
            data: ajaxData,
            success: function(response) {
                if (response.status !== 'success') {
                    revertInlineField($field);
                    setInlineState($field, 'Save failed', 'text-danger');
                    $('#inline-save-summary').removeClass('text-success text-muted').addClass('text-danger').text('Some changes could not be saved');
                    showRetryToast($field, response.message || 'Unable to save changes.');
                    return;
                }

                $field.attr('data-prev-value', value);
                setInlineState($field, 'Saved', 'text-success');
                $('#inline-save-summary').removeClass('text-danger text-muted').addClass('text-success').text('All changes saved');
            },
            error: function(xhr) {
                if (xhr && xhr.statusText === 'abort') {
                    return;
                }

                revertInlineField($field);
                setInlineState($field, 'Save failed', 'text-danger');
                $('#inline-save-summary').removeClass('text-success text-muted').addClass('text-danger').text('Some changes could not be saved');

                let errorMessage = 'Unable to save changes.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showRetryToast($field, errorMessage);
            },
            complete: function() {
                showFieldLoader($field, false);
                delete leadInlinePendingRequests[field];
            }
        });

        leadInlinePendingRequests[field] = request;
        return request;
    }

    function queueInlineAutosave($field, delayMs) {
        const key = $field.data('field');
        const currentValue = inlineFieldValue($field);
        const previousValue = ($field.attr('data-prev-value') || '').toString();

        if (currentValue === previousValue) {
            return;
        }

        if (leadInlineTimers[key]) {
            clearTimeout(leadInlineTimers[key]);
        }

        setInlineState($field, 'Unsaved change', 'text-muted');
        $('#inline-save-summary').removeClass('text-success text-danger').addClass('text-muted').text('Unsaved changes');

        leadInlineTimers[key] = setTimeout(function() {
            saveInlineField($field, { abortPrevious: true });
        }, delayMs || DROPDOWN_AUTO_SAVE_DELAY_MS);
    }

    // Auto-save all inline fields on change
    $('body').off('change.leadInlineQuickUpdate').on('change.leadInlineQuickUpdate', '.js-lead-inline-field', function() {
        const $field = $(this);
        if ($field.data('field') === 'country') {
            syncProfileCountryToCode();
            updateProfileMobilePrefix();
        }
        queueInlineAutosave($field, DROPDOWN_AUTO_SAVE_DELAY_MS);
    });

    // Auto-save text fields while typing (debounced)
    $('body').off('input.leadInlineQuickUpdateText').on('input.leadInlineQuickUpdateText', 'input.js-lead-inline-field, textarea.js-lead-inline-field', function() {
        const $field = $(this);
        if ($field.data('field') === 'mobile') {
            $field.val(inlineFieldValue($field));
        }
        queueInlineAutosave($field, TEXT_AUTO_SAVE_DELAY_MS);
    });

    $('body').off('click.leadInlineRetry').on('click.leadInlineRetry', '.js-inline-retry', function() {
        const field = $(this).data('field');
        const $field = $('.js-lead-inline-field[data-field="' + field + '"]').first();

        if ($field.length) {
            saveInlineField($field, { force: true });
        }
    });

    // Save button: save remaining non-autosave fields (also catches any pending changed field)
    $('body').off('click.leadInlineSaveAll').on('click.leadInlineSaveAll', '#save-inline-lead', function() {
        const $btn = $(this);
        const $allDirty = $('.js-lead-inline-field').filter(function() {
            const $el = $(this);
            return inlineFieldValue($el).toString() !== (($el.attr('data-prev-value') || '').toString());
        });

        let $fields = $allDirty;
        // Save country first so mobile normalization uses updated country code.
        const $countryField = $allDirty.filter('[data-field="country"]');
        const $mobileField = $allDirty.filter('[data-field="mobile"]');
        const $remaining = $allDirty.not('[data-field="country"]').not('[data-field="mobile"]');
        $fields = $().add($countryField).add($mobileField).add($remaining);

        if ($fields.length === 0) {
            $('#inline-save-summary').removeClass('text-danger text-muted').addClass('text-success').text('No pending changes');
            return;
        }

        const requests = [];
        $btn.prop('disabled', true);
        $('#inline-save-summary').removeClass('text-success text-danger').addClass('text-muted').text('Saving changes...');

        $fields.each(function() {
            requests.push(saveInlineField($(this)));
        });

        $.when.apply($, requests).always(function() {
            $btn.prop('disabled', false);

            const hasPending = $('.js-lead-inline-field').toArray().some(function(el) {
                const $el = $(el);
                return inlineFieldValue($el).toString() !== (($el.attr('data-prev-value') || '').toString());
            });

            if (!hasPending) {
                $('#inline-save-summary').removeClass('text-danger text-muted').addClass('text-success').text('All changes saved');
            }
        });
    });

    $('body').off('change.profileMobileCode').on('change.profileMobileCode', '#profile_mobile_country_code', function() {
        syncProfileCodeToCountry();
        updateProfileMobilePrefix();
    });

    syncProfileCountryToCode();
    updateProfileMobilePrefix();
</script>

