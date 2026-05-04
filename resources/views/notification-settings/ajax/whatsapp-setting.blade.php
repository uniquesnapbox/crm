<div class="col-xl-8 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
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
                    <div class="text-muted f-12 mt-1">
                        If left empty, the system uses <code>ADMIN_WHATSAPP</code>. This number is also used as the WhatsApp service session key.
                    </div>
                </div>

                <div class="col-lg-12 mt-3">
                    <x-forms.textarea :fieldLabel="'Lead Creation Message Template'" fieldName="lead_created_template"
                        fieldId="lead_created_template" fieldRequired="true"
                        :fieldValue="$whatsappSettings->lead_created_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_LEAD_CREATED_TEMPLATE"
                        fieldPlaceholder="Hello @{{client_name}}, thank you for your interest." />
                    <div class="text-muted f-12 mt-1">
                        Available placeholders: <code>@{{client_name}}</code>, <code>@{{company_name}}</code>, <code>@{{email}}</code>, <code>@{{mobile}}</code>, <code>@{{lead_id}}</code>, <code>@{{created_by}}</code>
                    </div>
                    <div class="text-muted f-12 mt-1">
                        The actual sending account must be logged in inside the WhatsApp service session for this same sender number.
                    </div>
                </div>

                <div class="col-lg-12 mt-4">
                    <h5 class="text-dark-grey f-15 mb-3">Ticket WhatsApp Templates</h5>
                </div>

                <div class="col-lg-12 mt-2">
                    <x-forms.textarea :fieldLabel="'Ticket Assigned Message for Staff'" fieldName="ticket_assigned_staff_template"
                        fieldId="ticket_assigned_staff_template" fieldRequired="true"
                        :fieldValue="$whatsappSettings->ticket_assigned_staff_template ?: 'A new ticket has been assigned to you. Ticket #{{ticket_number}}: {{subject}}'"
                        fieldPlaceholder="A new ticket has been assigned to you. Ticket #@{{ticket_number}}: @{{subject}}" />
                </div>

                <div class="col-lg-12 mt-3">
                    <x-forms.textarea :fieldLabel="'Ticket Assigned Message for Client'" fieldName="ticket_assigned_client_template"
                        fieldId="ticket_assigned_client_template" fieldRequired="true"
                        :fieldValue="$whatsappSettings->ticket_assigned_client_template ?: 'Your ticket #{{ticket_number}} has been forwarded to our team. We will get back to you soon.'"
                        fieldPlaceholder="Your ticket #@{{ticket_number}} has been forwarded to our team." />
                </div>

                <div class="col-lg-12 mt-3">
                    <x-forms.textarea :fieldLabel="'Ticket Resolved Message for Client'" fieldName="ticket_resolved_client_template"
                        fieldId="ticket_resolved_client_template" fieldRequired="true"
                        :fieldValue="$whatsappSettings->ticket_resolved_client_template ?: 'Your ticket #{{ticket_number}} has been resolved. If you need anything else, please let us know.'"
                        fieldPlaceholder="Your ticket #@{{ticket_number}} has been resolved." />
                    <div class="text-muted f-12 mt-1">
                        Available placeholders: <code>@{{ticket_number}}</code>, <code>@{{subject}}</code>, <code>@{{status}}</code>, <code>@{{priority}}</code>, <code>@{{agent_name}}</code>, <code>@{{client_name}}</code>
                    </div>
                </div>

                <div class="col-lg-12 mt-4">
                    <h5 class="text-dark-grey f-15 mb-3">Task WhatsApp Templates</h5>
                </div>

                <div class="col-lg-12 mt-2">
                    <x-forms.textarea :fieldLabel="'Task Assigned Message for Staff'" fieldName="task_assigned_staff_template"
                        fieldId="task_assigned_staff_template" fieldRequired="true"
                        :fieldValue="$whatsappSettings->task_assigned_staff_template ?: \App\Models\WhatsappNotificationSetting::DEFAULT_TASK_ASSIGNED_TEMPLATE"
                        fieldPlaceholder="A new task has been assigned to you. Task: @{{task_heading}}" />
                    <div class="text-muted f-12 mt-1">
                        Available placeholders: <code>@{{task_heading}}</code>, <code>@{{task_id}}</code>, <code>@{{project_name}}</code>, <code>@{{due_date}}</code>, <code>@{{task_status}}</code>, <code>@{{assigned_by}}</code>
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
    let whatsappQrPoller = null;
    let whatsappQrPollTick = 0;

    function loadWhatsAppConnectionStatus(forceRefresh = false) {
        const $serviceStatus = $('#whatsapp-service-status');
        const $sessionStatus = $('#whatsapp-session-status');
        const $meta = $('#whatsapp-connection-meta');
        const $error = $('#whatsapp-connection-error');
        const $image = $('#whatsapp-qr-image');
        const $placeholder = $('#whatsapp-qr-placeholder');

        $serviceStatus.removeClass('badge-success badge-danger badge-warning').addClass('badge-secondary').text('Checking service...');
        $sessionStatus.removeClass('badge-success badge-danger badge-warning').addClass('badge-secondary').text('Checking session...');
        $error.addClass('d-none').text('');

        const statusUrl = "{{ route('whatsapp-settings.connection-status') }}" + (forceRefresh ? "?refresh=1" : "");

        $.easyAjax({
            url: statusUrl,
            type: "GET",
            container: "#editSettings",
            success: function (response) {
                const health = response.health || {};
                const qr = response.qr || {};
                const qrData = qr.data || {};
                const sessionKey = response.sessionKey || qrData.sessionKey || 'default';
                const sessionStatus = qrData.status || 'unknown';
                const isReady = Boolean(health.data && health.data.ready);

                $serviceStatus.removeClass('badge-secondary').addClass(health.success ? (isReady ? 'badge-success' : 'badge-warning') : 'badge-danger');
                $serviceStatus.text(health.success ? (isReady ? 'Service Ready' : 'Service Online') : 'Service Error');

                $sessionStatus.removeClass('badge-secondary').addClass(
                    sessionStatus === 'ready' ? 'badge-success' :
                    (sessionStatus === 'qr_required' ? 'badge-warning' : 'badge-danger')
                );
                $sessionStatus.text('Session: ' + sessionStatus.replace(/_/g, ' '));

                $meta.html('Session: <code>' + sessionKey + '</code>' + (response.baseUrl ? ' | URL: <code>' + response.baseUrl + '</code>' : ''));

                if (qr.image) {
                    $image.attr('src', qr.image).removeClass('d-none');
                    $placeholder.addClass('d-none');
                } else {
                    $image.attr('src', '').addClass('d-none');
                    $placeholder.removeClass('d-none').text(
                        sessionStatus === 'ready'
                            ? 'WhatsApp is already connected for this session.'
                            : 'QR is not available yet. If this stays empty, make sure the Node WhatsApp service is running and requesting login.'
                    );
                }

                const errors = [health.error, qr.error].filter(Boolean).join(' ');
                if (errors) {
                    $error.removeClass('d-none').text(errors);
                }
            },
            error: function () {
                $serviceStatus.removeClass('badge-secondary').addClass('badge-danger').text('Service Error');
                $sessionStatus.removeClass('badge-secondary').addClass('badge-danger').text('Session Error');
                $('#whatsapp-connection-error').removeClass('d-none').text('Unable to fetch WhatsApp connection details from CRM.');
            }
        });
    }

    $('body').on('click', '#refresh-whatsapp-connection', function() {
        loadWhatsAppConnectionStatus(true);
    });

    $('body').on('click', '#save-whatsapp-form', function() {
        $.easyAjax({
            url: "{{ route('whatsapp-settings.update', $whatsappSettings->id ?: 1) }}",
            type: "POST",
            container: "#editSettings",
            blockUI: true,
            data: $('#editSettings').serialize(),
            success: function () {
                window.location.reload();
            }
        })
    });

    loadWhatsAppConnectionStatus(true);

    if (whatsappQrPoller) {
        clearInterval(whatsappQrPoller);
    }

    whatsappQrPoller = setInterval(function () {
        whatsappQrPollTick++;
        const force = whatsappQrPollTick % 3 === 0; // force refresh roughly every 45 seconds
        loadWhatsAppConnectionStatus(force);
    }, 15000);
</script>
