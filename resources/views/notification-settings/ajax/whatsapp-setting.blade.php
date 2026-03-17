<div class="col-xl-8 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4 ">
    <div class="row" id="whatsapp-row">
        <div class="col-lg-12">
            <x-forms.checkbox fieldLabel="Enable WhatsApp notifications" fieldName="whatsapp_status" fieldId="whatsapp_status"
                fieldValue="active" fieldRequired="true" :checked="$whatsappSettings->status == 'active'" />
        </div>

        <div class="col-lg-12 whatsapp_details @if($whatsappSettings->status == 'inactive') d-none @endif">
            <div class="row mt-3">
                <div class="col-lg-12">
                    <x-forms.text :fieldLabel="'Base URL'" fieldName="base_url" fieldId="base_url"
                        :fieldValue="$whatsappSettings->base_url" fieldRequired="true" />
                </div>

                <div class="col-lg-12">
                    <x-forms.text :fieldLabel="'API Token'" fieldName="api_token" fieldId="api_token"
                        :fieldValue="$whatsappSettings->api_token" fieldRequired="true" />
                </div>

                <div class="col-lg-6 col-md-6">
                    <x-forms.text :fieldLabel="'Default Country Code'" fieldName="default_country_code" fieldId="default_country_code"
                        :fieldValue="$whatsappSettings->default_country_code" fieldRequired="true" />
                </div>

                <div class="col-lg-6 col-md-6">
                    <x-forms.text :fieldLabel="'Test Number'" fieldName="test_number" fieldId="test_number"
                        :fieldValue="$whatsappSettings->test_number" />
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded">
                <div class="d-flex flex-wrap align-items-center mb-2">
                    <span class="f-14 text-dark-grey mr-2">Last WhatsApp send status:</span>
                    @php
                        $status = $whatsappSettings->last_send_status;
                        $statusClass = $status === 'sent' ? 'badge badge-success' : ($status === 'accepted' ? 'badge badge-warning' : ($status === 'failed' ? 'badge badge-danger' : 'badge badge-secondary'));
                    @endphp
                    <span class="{{ $statusClass }}">{{ $whatsappSettings->delivery_status_label }}</span>
                </div>

                @if (!empty($whatsappSettings->last_error_message))
                    <div class="f-13 text-danger mb-2">
                        <strong>Last error message:</strong> {{ $whatsappSettings->last_error_message }}
                    </div>
                @endif

                @if (!empty($whatsappSettings->last_http_status))
                    <div class="f-13 text-dark-grey mb-1">
                        <strong>Last HTTP status:</strong> {{ $whatsappSettings->last_http_status }}
                    </div>
                @endif

                @if (!empty($whatsappSettings->last_normalized_phone))
                    <div class="f-13 text-dark-grey mb-1">
                        <strong>Final normalized phone:</strong> {{ $whatsappSettings->last_normalized_phone }}
                    </div>
                @endif

                @if (!empty($whatsappSettings->last_response_message))
                    <div class="f-13 text-dark-grey mb-1">
                        <strong>Exact API response message:</strong> {{ $whatsappSettings->last_response_message }}
                    </div>
                @endif

                @if (!empty($whatsappSettings->last_response_body))
                    <div class="f-13 text-dark-grey mb-1">
                        <strong>Raw API response body:</strong>
                        <pre class="mt-1 mb-0 p-2 bg-white border rounded text-wrap">{{ $whatsappSettings->last_response_body }}</pre>
                    </div>
                @endif

                @if (!empty($whatsappSettings->last_delivery_status))
                    <div class="f-13 text-dark-grey mb-1">
                        <strong>Delivery interpretation:</strong>
                        @if ($whatsappSettings->last_delivery_status === 'sent')
                            Sent confirmed by API response.
                        @elseif ($whatsappSettings->last_delivery_status === 'accepted')
                            Request accepted by API only. Handset delivery is not confirmed.
                        @elseif ($whatsappSettings->last_delivery_status === 'failed')
                            Request failed.
                        @endif
                    </div>
                @endif

                @if (!empty($whatsappSettings->last_sent_at))
                    <div class="f-13 text-dark-grey">
                        <strong>Last attempt:</strong> {{ $whatsappSettings->last_sent_at->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="col-xl-4 col-lg-12 col-md-12 ntfcn-tab-content-right border-left-grey p-4">
    <h4 class="f-16 text-capitalize f-w-500 text-dark-grey">WhatsApp Notification Settings</h4>
    <div class="mb-3 d-flex">
        <x-forms.checkbox :checked="$checkedAll == true"
            :fieldLabel="__('modules.permission.selectAll')"
            fieldName="select_all_checkbox" fieldId="select_all_whatsapp"
            fieldValue="all"/>
    </div>
    @foreach ($whatsappEventSettings as $emailSetting)
        <div class="mb-3 d-flex notification">
            <x-forms.checkbox :checked="$emailSetting->send_whatsapp == 'yes'"
                :fieldLabel="__('modules.emailNotification.'.str_slug($emailSetting->setting_name))"
                fieldName="send_whatsapp[]" :fieldId="'send_whatsapp_'.$emailSetting->id" :fieldValue="$emailSetting->id" />
        </div>
    @endforeach
</div>

<div class="w-100 border-top-grey set-btns">
    <x-setting-form-actions>
        <x-forms.button-primary id="save-whatsapp-form" class="mr-3" icon="check">@lang('app.save')
        </x-forms.button-primary>

        <x-forms.button-secondary id="send-test-whatsapp" icon="location-arrow">
            Send Test WhatsApp</x-forms.button-secondary>
    </x-setting-form-actions>
</div>

<script>
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

    $('body').on('click', '#send-test-whatsapp', function() {
        $.easyAjax({
            url: "{{ route('whatsapp_settings.send_test_notification') }}",
            type: "GET",
            success: function () {
                window.location.reload();
            }
        })
    });

    var whatsappCheckboxes = document.querySelectorAll(".notification input[type=checkbox]");

    $('body').on('click', '#select_all_whatsapp', function() {
        var selectAll = $('#select_all_whatsapp').is(':checked');

        whatsappCheckboxes.forEach(function(checkbox){
            checkbox.checked = selectAll;
        })
    });
</script>
