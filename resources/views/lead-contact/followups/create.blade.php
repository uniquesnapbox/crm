<div class="modal-header">
    <h5 class="modal-title">@lang('modules.lead.addFollowUp')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <x-form id="leadFollowUpForm" method="POST" class="ajax-form">
        <div class="form-body">
            <div class="row">
                <div class="col-md-12">
                    <x-cards.data-row :label="__('modules.lead.clientName')" :value="$leadContact->client_name ?? '--'" />
                </div>
                <div class="col-md-6">
                    <x-forms.datepicker fieldId="next_follow_up_date" fieldRequired="true"
                        :fieldLabel="__('modules.lead.leadFollowUp')" fieldName="next_follow_up_date"
                        :fieldValue="now(company()->timezone)->format(company()->date_format)"
                        :fieldPlaceholder="__('placeholders.date')" />
                </div>
                <div class="col-md-6">
                    <div class="bootstrap-timepicker timepicker">
                        <x-forms.text :fieldLabel="__('modules.timeLogs.startTime')" fieldName="start_time"
                            fieldId="start_time" fieldRequired="true"
                            :fieldValue="now(company()->timezone)->format(company()->time_format)" />
                    </div>
                </div>
                <div class="col-lg-12 my-3">
                    <x-forms.checkbox :fieldLabel="__('modules.tasks.reminder')" fieldName="send_reminder"
                        fieldId="send_reminder" fieldValue="yes" />
                </div>
                <div class="col-lg-12 send_reminder_div d-none">
                    <div class="row">
                        <div class="col-lg-6 mt-1">
                            <x-forms.number :fieldLabel="__('modules.events.remindBefore')" fieldName="remind_time"
                                fieldId="remind_time" fieldValue="" />
                        </div>
                        <div class="col-md-6 mt-3">
                            <x-forms.select fieldId="remind_type" fieldLabel="" fieldName="remind_type" search="true">
                                <option value="day">@lang('app.day')</option>
                                <option value="hour">@lang('app.hour')</option>
                                <option value="minute">@lang('app.minute')</option>
                            </x-forms.select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <x-forms.text :fieldLabel="__('app.latitude')" fieldName="latitude" fieldId="latitude" />
                </div>
                <div class="col-md-6">
                    <x-forms.text :fieldLabel="__('app.longitude')" fieldName="longitude" fieldId="longitude" />
                </div>
                <div class="col-md-12">
                    <div class="form-group my-3">
                        <x-forms.textarea :fieldLabel="__('modules.lead.remark')" fieldName="remark" fieldId="remark"></x-forms.textarea>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="lead_id" value="{{ $leadId }}">
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    <x-forms.button-primary id="save-lead-followup" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(".select-picker").selectpicker();

    $('#start_time').timepicker({
        @if (company()->time_format == 'H:i')
        showMeridian: false,
        @endif
    });

    const leadFollowUpDate = datepicker('#next_follow_up_date', {
        position: 'bl',
        ...datepickerConfig
    });

    leadFollowUpDate.setMin(new Date());

    $('#send_reminder').change(function() {
        $('.send_reminder_div').toggleClass('d-none');
    });

    $('#save-lead-followup').click(function() {
        $.easyAjax({
            url: "{{ route('lead-contact.follow_up_store') }}",
            container: '#leadFollowUpForm',
            type: "POST",
            blockUI: true,
            data: $('#leadFollowUpForm').serialize(),
            success: function(response) {
                if (response.status === "success") {
                    window.location.href = "{{ route('lead-contact.show', $leadId) }}?tab=follow-up";
                }
            }
        });
    });
</script>
