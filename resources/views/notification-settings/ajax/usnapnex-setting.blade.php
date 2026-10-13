<style>
    .usnapnex-api-card {
        max-width: 850px;
        border: 1px solid #e7ebf0;
        border-radius: 10px;
        background: #fff;
    }

    .usnapnex-api-card .form-control {
        height: 42px;
    }

    .usnapnex-api-help {
        color: #6c757d;
        font-size: 12px;
        line-height: 1.55;
    }
</style>

<div class="col-xl-12 col-lg-12 col-md-12 w-100 p-4">
    <input type="hidden" name="usnapnex_only" value="1">
    <div class="usnapnex-api-card p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-4">
            <div class="mr-3">
                <h5 class="text-dark-grey f-15 mb-1">USNAPNEX WhatsApp API</h5>
                <p class="usnapnex-api-help mb-0">
                    Add your WAHA API details here. CRM messages will be sent through this server-side connection.
                </p>
            </div>
            <span class="badge badge-light mt-2 mt-md-0">API key stays private</span>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label for="api_driver">API Driver</label>
                <select class="form-control" name="api_driver" id="api_driver">
                    <option value="waha" @selected(($whatsappSettings->api_driver ?? 'waha') === 'waha')>WAHA</option>
                    <option value="bridge" @selected(($whatsappSettings->api_driver ?? '') === 'bridge')>Legacy Bridge</option>
                </select>
            </div>

            <div class="col-md-6 form-group">
                <label for="api_session">Session Name</label>
                <input type="text" class="form-control" name="api_session" id="api_session"
                    value="{{ $whatsappSettings->api_session ?: 'default' }}" placeholder="default">
                <div class="usnapnex-api-help mt-1">Usually <code>default</code>.</div>
            </div>

            <div class="col-md-12 form-group">
                <label for="api_base_url">WAHA API URL</label>
                <input type="url" class="form-control" name="api_base_url" id="api_base_url"
                    value="{{ $whatsappSettings->base_url ?: config('services.whatsapp_service.base_url') }}"
                    placeholder="http://127.0.0.1:3000">
                <div class="usnapnex-api-help mt-1">Local example: <code>http://127.0.0.1:3000</code> | VPS example: <code>http://66.116.234.170:3001</code></div>
            </div>

            <div class="col-md-12 form-group">
                <label for="api_key">WAHA API Key</label>
                <input type="password" class="form-control" name="api_key" id="api_key"
                    value="" autocomplete="new-password" placeholder="Paste WAHA_API_KEY here">
                <div class="usnapnex-api-help mt-1">
                    @if(filled($whatsappSettings->api_token))
                        An API key is already saved. Leave blank to keep it unchanged.
                    @else
                        Use the same value as <code>WAHA_API_KEY</code> from the WAHA server.
                    @endif
                </div>
            </div>
        </div>

        <div class="alert alert-info usnapnex-api-help mb-0">
            After saving, open <strong>WhatsApp API / QR</strong> and scan the QR code. Once the session shows <strong>WORKING</strong>, CRM messages will use USNAPNEX.
        </div>
    </div>
</div>

<div class="w-100 border-top-grey set-btns">
    <x-setting-form-actions>
        <x-forms.button-primary id="save-usnapnex-form" class="mr-3" icon="check">Save USNAPNEX API</x-forms.button-primary>
        <button type="button" class="btn btn-outline-secondary" id="test-usnapnex-api">Test Connection</button>
        <span class="small ml-3" id="usnapnex-api-test-result"></span>
    </x-setting-form-actions>
</div>

<script>
    (function initialiseUsnapnexSettings() {
        $('body').off('click.usnapnexSave', '#save-usnapnex-form').on('click.usnapnexSave', '#save-usnapnex-form', function () {
            $.easyAjax({
                url: "{{ route('whatsapp-settings.update', $whatsappSettings->id ?: 1) }}",
                type: 'POST',
                container: '#editSettings',
                blockUI: true,
                data: $('#editSettings').serialize(),
                success: function () {
                    window.location.href = "{{ route('notifications.index', ['tab' => 'usnapnex-setting']) }}";
                }
            });
        });

        $('body').off('click.usnapnexTest', '#test-usnapnex-api').on('click.usnapnexTest', '#test-usnapnex-api', function () {
            const $result = $('#usnapnex-api-test-result');
            $result.removeClass('text-success text-danger').addClass('text-muted').text('Checking...');

            $.getJSON("{{ route('whatsapp-settings.connection-status') }}")
                .done(function (response) {
                    const health = response && response.health ? response.health : {};
                    const session = response && response.sessionKey ? response.sessionKey : '--';
                    const status = health.data && health.data.sessions && health.data.sessions[0]
                        ? health.data.sessions[0].status
                        : 'unknown';
                    $result.removeClass('text-muted text-danger').addClass(health.success ? 'text-success' : 'text-danger')
                        .text((health.success ? 'Connected' : 'Error') + ' | Session: ' + session + ' | ' + status);
                })
                .fail(function () {
                    $result.removeClass('text-muted text-success').addClass('text-danger').text('Unable to connect. Save settings first.');
                });
        });
    })();
</script>
