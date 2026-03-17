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
                        :fieldValue="$whatsappSettings->default_country_code" />
                </div>

                <div class="col-lg-6 col-md-6">
                    <x-forms.text :fieldLabel="'Test Number'" fieldName="test_number" fieldId="test_number"
                        :fieldValue="$whatsappSettings->test_number" />
                </div>
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
