<style>
    .wa-section-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        background: #fff;
        padding: 14px;
    }

    .wa-section-title {
        font-size: 15px;
        font-weight: 600;
        color: #2f3a4a;
        margin-bottom: 2px;
    }

    .wa-section-subtitle {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 10px;
    }

    .wa-msg-card {
        border: 1px solid #e8ebf0;
        border-radius: 8px;
        background: #fafbfd;
        padding: 12px;
        height: 100%;
    }

    .wa-msg-card .form-group {
        margin-top: 0 !important;
        margin-bottom: 8px !important;
    }

    .wa-msg-card .form-group:last-child {
        margin-bottom: 0 !important;
    }

    .wa-msg-title {
        font-size: 14px;
        font-weight: 600;
        color: #2f3a4a;
        margin-bottom: 2px;
    }

    .wa-placeholder {
        font-size: 12px;
        color: #6c757d;
        line-height: 1.35;
        margin-top: 4px;
    }

    .wa-grid-gap {
        row-gap: 12px;
    }
</style>

<div class="col-xl-12 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
    <div class="row" id="whatsapp-row">
        <div class="col-lg-12">
            <div class="row mt-3">
                <div class="col-lg-12 mt-2">
                    <div class="card border-grey rounded">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="text-dark-grey f-15 mb-1">WhatsApp Connection</h5>
                                    <div class="text-muted f-12">
                                        Scan this QR from the WhatsApp account you want to connect with the microservice.
                                    </div>
                                    <div class="text-muted f-12 mt-1">
                                        If the QR is missing, this panel will re-check the bridge automatically and keep your existing WhatsApp login intact.
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 mt-lg-0" id="refresh-whatsapp-connection">
                                    Refresh QR
                                </button>
                            </div>

                            <div class="mb-3">
                                <span class="badge badge-secondary mr-2" id="whatsapp-service-status">Checking service...</span>
                                <span class="badge badge-secondary" id="whatsapp-session-status">Checking session...</span>
                            </div>

                            <div class="text-muted f-12 mb-3" id="whatsapp-connection-meta">
                                Session: <code>--</code>
                            </div>

                            <div class="alert alert-warning d-none" id="whatsapp-connection-error"></div>

                            <div class="text-center border rounded p-3 bg-white" id="whatsapp-qr-wrapper">
                                <img src="" alt="WhatsApp QR" id="whatsapp-qr-image" class="img-fluid d-none" style="max-height: 360px; image-rendering: pixelated;">
                                <div class="text-muted f-13" id="whatsapp-qr-placeholder">
                                    QR will appear here when the WhatsApp service requests login.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 mt-3">
                    <x-forms.text :fieldLabel="'Sender WhatsApp Number'" fieldName="lead_created_sender_number"
                        fieldId="lead_created_sender_number"
                        :fieldValue="$whatsappSettings->lead_created_sender_number ?: config('app.admin_whatsapp', '')"
                        :fieldPlaceholder="'Defaults to admin WhatsApp number'" />
                    <div class="wa-placeholder">If left empty, system uses <code>ADMIN_WHATSAPP</code> and the same value is used as session key.</div>
                </div>

                <div class="col-lg-12 mt-4">
                    <div class="wa-section-title">Lead Messages</div>
                    <div class="wa-section-subtitle">Messages for lead creation, product-interest updates, and follow-up reminders.</div>
                    <div class="row wa-grid-gap">
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Lead Creation Message</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_lead_created_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_lead_created_message"
                                    fieldId="send_lead_created_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="lead_created_template"
                                    fieldId="lead_created_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->lead_created_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE"
                                    fieldPlaceholder="Hello @{{client_name}}, thank you for your interest." />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{client_name}}</code>, <code>@{{company_name}}</code>, <code>@{{email}}</code>, <code>@{{mobile}}</code>, <code>@{{lead_id}}</code>, <code>@{{created_by}}</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Lead Product-Interest Message</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_lead_interest_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_lead_interest_message"
                                    fieldId="send_lead_interest_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="lead_interest_template"
                                    fieldId="lead_interest_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->lead_interest_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_LEAD_INTEREST_TEMPLATE"
                                    fieldPlaceholder="Hello @{{client_name}}, thank you for sharing your interest in @{{products_services}}." />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{client_name}}</code>, <code>@{{products_services}}</code>, <code>@{{company_name}}</code>, <code>@{{mobile}}</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Lead Follow-up Reminder</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_lead_followup_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_lead_followup_message"
                                    fieldId="send_lead_followup_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="lead_followup_template"
                                    fieldId="lead_followup_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->lead_followup_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_LEAD_FOLLOWUP_TEMPLATE"
                                    fieldPlaceholder="Hello @{{user_name}}, follow-up reminder for @{{lead_name}}. Client Number: @{{contact}}, Remarks: @{{note}}, Call Time: @{{follow_up_time}}" />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{user_name}}</code>, <code>@{{lead_name}}</code>, <code>@{{client_name}}</code>, <code>@{{follow_up_time}}</code>, <code>@{{call_time}}</code>, <code>@{{contact}}</code>, <code>@{{lead_mobile}}</code>, <code>@{{note}}</code>, <code>@{{remarks}}</code>, <code>@{{company_name}}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 mt-4">
                    <div class="wa-section-title">Ticket Messages</div>
                    <div class="wa-section-subtitle">Messages for ticket assignment and ticket resolution.</div>
                    <x-forms.toggle-switch class="mb-2 mt-0"
                        :checked="($whatsappSettings->send_ticket_message ?? 'yes') === 'yes'"
                        :fieldLabel="'Enable Ticket WhatsApp Messages'" fieldName="send_ticket_message"
                        fieldId="send_ticket_message" />
                    <div class="row wa-grid-gap">
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Assigned Message for Staff</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_ticket_assigned_staff_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_ticket_assigned_staff_message"
                                    fieldId="send_ticket_assigned_staff_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="ticket_assigned_staff_template"
                                    fieldId="ticket_assigned_staff_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->ticket_assigned_staff_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_STAFF_TEMPLATE"
                                    fieldPlaceholder="A new ticket has been assigned to you. Ticket #@{{ticket_number}}: @{{subject}}" />
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Resolved Message for Client</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_ticket_resolved_client_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_ticket_resolved_client_message"
                                    fieldId="send_ticket_resolved_client_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="ticket_resolved_client_template"
                                    fieldId="ticket_resolved_client_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->ticket_resolved_client_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TICKET_RESOLVED_CLIENT_TEMPLATE"
                                    fieldPlaceholder="Your ticket #@{{ticket_number}} has been resolved." />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{ticket_number}}</code>, <code>@{{subject}}</code>, <code>@{{status}}</code>, <code>@{{priority}}</code>, <code>@{{agent_name}}</code>, <code>@{{client_name}}</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Assigned Message for Client</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_ticket_assigned_client_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_ticket_assigned_client_message"
                                    fieldId="send_ticket_assigned_client_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="ticket_assigned_client_template"
                                    fieldId="ticket_assigned_client_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->ticket_assigned_client_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TICKET_ASSIGNED_CLIENT_TEMPLATE"
                                    fieldPlaceholder="Your ticket #@{{ticket_number}} has been forwarded to our team." />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 mt-4">
                    <div class="wa-section-title">Task Messages</div>
                    <div class="wa-section-subtitle">Messages for assignment, pending summary, and completion.</div>
                    <div class="row wa-grid-gap">
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Assigned Message for Staff</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_task_assigned_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_task_assigned_message"
                                    fieldId="send_task_assigned_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="task_assigned_staff_template"
                                    fieldId="task_assigned_staff_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->task_assigned_staff_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE"
                                    fieldPlaceholder="Hello @{{user_name}}, a new task has been assigned to you. Task: @{{task_heading}}" />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{user_name}}</code>, <code>@{{task_heading}}</code>, <code>@{{task_id}}</code>, <code>@{{project_name}}</code>, <code>@{{due_date}}</code>, <code>@{{task_status}}</code>, <code>@{{assigned_by}}</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Daily Pending Task Summary</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_task_daily_pending_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_task_daily_pending_message"
                                    fieldId="send_task_daily_pending_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="task_daily_pending_template"
                                    fieldId="task_daily_pending_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->task_daily_pending_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TASK_DAILY_PENDING_TEMPLATE"
                                    fieldPlaceholder="Good morning @{{user_name}}, you have @{{pending_count}} pending task(s): @{{task_list}}" />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{user_name}}</code>, <code>@{{pending_count}}</code>, <code>@{{task_list}}</code>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="wa-msg-card">
                                <div class="wa-msg-title">Task Completed Message</div>
                                <x-forms.toggle-switch class="mb-2 mt-0"
                                    :checked="($whatsappSettings->send_task_completed_message ?? 'yes') === 'yes'"
                                    :fieldLabel="'Enable'" fieldName="send_task_completed_message"
                                    fieldId="send_task_completed_message" />
                                <x-forms.textarea :fieldLabel="'Template'" fieldName="task_completed_template"
                                    fieldId="task_completed_template" fieldRequired="true"
                                    :fieldValue="$whatsappSettings->task_completed_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TASK_COMPLETED_TEMPLATE"
                                    fieldPlaceholder="Task completed: @{{task_heading}}" />
                                <div class="wa-placeholder">
                                    Placeholders: <code>@{{user_name}}</code>, <code>@{{task_heading}}</code>, <code>@{{task_id}}</code>, <code>@{{project_name}}</code>, <code>@{{completed_on}}</code>, <code>@{{completed_by}}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="w-100 border-top-grey set-btns">
    <x-setting-form-actions>
        <x-forms.button-primary id="save-whatsapp-form" class="mr-3" icon="check">@lang('app.save')
        </x-forms.button-primary>
    </x-setting-form-actions>
</div>

<script>
    (function initialiseWhatsAppConnectionPanel() {
        const previousState = window.whatsappConnectionPanelState;

        if (previousState && previousState.poller) {
            window.clearInterval(previousState.poller);
        }

        const state = {
            poller: null,
            requestInFlight: false,
            refreshInFlight: false,
            lastRefreshAt: 0,
            initialRefreshTriggered: false
        };

        window.whatsappConnectionPanelState = state;
        function stopPollingWhenPanelIsMissing() {
            if ($('#whatsapp-service-status').length) {
                return false;
            }

            if (state.poller) {
                window.clearInterval(state.poller);
                state.poller = null;
            }

            return true;
        }

        function loadWhatsAppConnectionStatus(forceRefresh) {
            forceRefresh = Boolean(forceRefresh);

            if (stopPollingWhenPanelIsMissing() || state.requestInFlight || (forceRefresh && state.refreshInFlight)) {
                return;
            }

            const $serviceStatus = $('#whatsapp-service-status');
            const $sessionStatus = $('#whatsapp-session-status');
            const $meta = $('#whatsapp-connection-meta');
            const $error = $('#whatsapp-connection-error');
            const $image = $('#whatsapp-qr-image');
            const $placeholder = $('#whatsapp-qr-placeholder');
            const $wrapper = $('#whatsapp-qr-wrapper');

            state.requestInFlight = true;

            if (forceRefresh) {
                state.refreshInFlight = true;
                state.lastRefreshAt = Date.now();
            }

            $serviceStatus.removeClass('badge-success badge-danger badge-warning').addClass('badge-secondary').text('Checking service...');
            $sessionStatus.removeClass('badge-success badge-danger badge-warning').addClass('badge-secondary').text('Checking session...');
            $error.addClass('d-none').text('');

            $.ajax({
                url: "{{ route('whatsapp-settings.connection-status') }}",
                type: 'GET',
                dataType: 'json',
                cache: false,
                data: forceRefresh ? { refresh: 1 } : {}
            }).done(function (response) {
                const health = response && response.health ? response.health : {};
                const healthData = health.data || {};
                const qr = response && response.qr ? response.qr : {};
                const qrData = qr.data || {};
                const healthSessions = Array.isArray(healthData.sessions) ? healthData.sessions : [];
                const requestedSessionKey = String((response && response.sessionKey) || qrData.sessionKey || '');
                const healthSession = healthSessions.find(function (session) {
                    return String(session.sessionKey || '') === requestedSessionKey;
                }) || healthSessions[0] || {};
                const sessionKey = requestedSessionKey || String(healthSession.sessionKey || '');
                const connectionStatus = String(qrData.status || healthSession.status || 'unknown').toLowerCase();
                // Global health can be ready because a different session is connected.
                const isConnected = connectionStatus === 'ready';
                const generatedAt = qrData.generatedAt ? new Date(qrData.generatedAt) : null;

                $serviceStatus
                    .removeClass('badge-secondary badge-success badge-danger badge-warning')
                    .addClass(health.success ? (isConnected ? 'badge-success' : 'badge-warning') : 'badge-danger')
                    .text(health.success ? (isConnected ? 'Connected / Running' : 'Running / Waiting') : 'Service Error');

                $sessionStatus
                    .removeClass('badge-secondary badge-success badge-danger badge-warning')
                    .addClass(isConnected ? 'badge-success' : (connectionStatus === 'qr_required' ? 'badge-warning' : 'badge-danger'))
                    .text(sessionKey ? 'Session: ' + sessionKey : 'Session unavailable');

                let metaHtml = 'Session: <code>' + (sessionKey || '--') + '</code>';
                metaHtml += ' | Status: <strong>' + (isConnected ? 'Connected' : connectionStatus.replace(/_/g, ' ')) + '</strong>';

                if (response.baseUrl) {
                    metaHtml += ' | URL: <code>' + response.baseUrl + '</code>';
                }

                if (generatedAt && !Number.isNaN(generatedAt.getTime())) {
                    metaHtml += ' | Fresh QR: <strong>' + generatedAt.toLocaleTimeString() + '</strong>';
                }

                $meta.html(metaHtml);
                $wrapper.toggleClass('border-success', isConnected);
                $placeholder.removeClass('text-success text-muted');

                if (isConnected) {
                    $image.attr('src', '').addClass('d-none');
                    $placeholder
                        .removeClass('d-none')
                        .addClass('text-success')
                        .text('WhatsApp is connected. QR is hidden for this active session.');
                } else if (qr.image) {
                    $image.attr('src', qr.image).removeClass('d-none');
                    $placeholder.addClass('d-none');
                } else {
                    $image.attr('src', '').addClass('d-none');
                    $placeholder
                        .removeClass('d-none')
                        .addClass('text-muted')
                        .text('QR is not available yet. The panel will retry once automatically and you can also use Refresh QR.');
                }

                const errors = [health.error, qr.error].filter(Boolean).join(' ');
                if (errors) {
                    $error.removeClass('d-none').text(errors);
                }

                const qrAge = generatedAt && !Number.isNaN(generatedAt.getTime())
                    ? Date.now() - generatedAt.getTime()
                    : 0;
                const refreshCooldownElapsed = Date.now() - state.lastRefreshAt > 45000;

                if (!forceRefresh && connectionStatus === 'qr_required' && qrAge > 45000 &&
                    refreshCooldownElapsed && !state.refreshInFlight) {
                    window.setTimeout(function () {
                        loadWhatsAppConnectionStatus(true);
                    }, 0);
                }

                if (!forceRefresh && !state.initialRefreshTriggered && !isConnected && !qr.image) {
                    state.initialRefreshTriggered = true;
                    window.setTimeout(function () {
                        loadWhatsAppConnectionStatus(true);
                    }, 1200);
                }
            }).fail(function (jqXHR) {
                const message = jqXHR && jqXHR.status === 401
                    ? 'Your login session expired. Reload the page and sign in again.'
                    : 'Unable to fetch WhatsApp connection details from CRM.';

                $serviceStatus.removeClass('badge-secondary badge-success badge-warning').addClass('badge-danger').text('Service Error');
                $sessionStatus.removeClass('badge-secondary badge-success badge-warning').addClass('badge-danger').text('Session unavailable');
                $error.removeClass('d-none').text(message);
            }).always(function () {
                state.requestInFlight = false;

                if (forceRefresh) {
                    state.refreshInFlight = false;
                }
            });
        }

        $('body')
            .off('click.whatsappConnection', '#refresh-whatsapp-connection')
            .on('click.whatsappConnection', '#refresh-whatsapp-connection', function () {
                loadWhatsAppConnectionStatus(true);
            });

        $('body')
            .off('click.whatsappConnection', '#save-whatsapp-form')
            .on('click.whatsappConnection', '#save-whatsapp-form', function () {
                $.easyAjax({
                    url: "{{ route('whatsapp-settings.update', $whatsappSettings->id ?: 1) }}",
                    type: 'POST',
                    container: '#editSettings',
                    blockUI: true,
                    data: $('#editSettings').serialize(),
                    success: function () {
                        window.location.reload();
                    }
                });
            });

        loadWhatsAppConnectionStatus(false);
        state.poller = window.setInterval(function () {
            loadWhatsAppConnectionStatus(false);
        }, 15000);
    })();
</script>
