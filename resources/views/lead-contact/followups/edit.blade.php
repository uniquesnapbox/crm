<div class="modal-header">
    <h5 class="modal-title">@lang('modules.lead.editFollowUp')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body">
    <x-form id="leadFollowUpForm" method="POST" class="ajax-form">
        <input type="hidden" name="id" value="{{ $follow->id }}">
        <div class="form-body">
            <div class="row">
                <div class="col-md-6">
                    <x-forms.datepicker fieldId="next_follow_up_date" fieldRequired="true"
                        :fieldLabel="__('modules.lead.leadFollowUp')" fieldName="next_follow_up_date"
                        :fieldValue="$follow->next_follow_up_date->timezone(company()->timezone)->format(company()->date_format)"
                        :fieldPlaceholder="__('placeholders.date')" />
                </div>
                <div class="col-md-6">
                    <div class="bootstrap-timepicker timepicker">
                        <x-forms.text :fieldLabel="__('modules.timeLogs.startTime')" fieldName="start_time"
                            fieldId="start_time" fieldRequired="true"
                            :fieldValue="$follow->next_follow_up_date->timezone(company()->timezone)->format(company()->time_format)" />
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <x-forms.select fieldId="status" :fieldLabel="__('modules.employees.status')" fieldName="status" search="true">
                        <option value="pending" @selected($follow->status == 'pending')>@lang('app.pending')</option>
                        <option value="canceled" @selected($follow->status == 'canceled')>@lang('app.canceled')</option>
                        <option value="completed" @selected($follow->status == 'completed')>@lang('app.completed')</option>
                    </x-forms.select>
                </div>
                <div class="col-md-6 mt-5">
                    <x-forms.checkbox :fieldLabel="__('modules.tasks.reminder')" fieldName="send_reminder"
                        fieldId="send_reminder" fieldValue="yes" :checked="$follow->send_reminder == 'yes'" />
                </div>
                <div class="col-lg-12 send_reminder_div {{ $follow->send_reminder == 'yes' ? '' : 'd-none' }}">
                    <div class="row">
                        <div class="col-lg-6 mt-1">
                            <x-forms.number :fieldLabel="__('modules.events.remindBefore')" fieldName="remind_time"
                                fieldId="remind_time" :fieldValue="$follow->remind_time" />
                        </div>
                        <div class="col-md-6 mt-3">
                            <x-forms.select fieldId="remind_type" fieldLabel="" fieldName="remind_type" search="true">
                                <option value="day" @selected($follow->remind_type == 'day')>@lang('app.day')</option>
                                <option value="hour" @selected($follow->remind_type == 'hour')>@lang('app.hour')</option>
                                <option value="minute" @selected($follow->remind_type == 'minute')>@lang('app.minute')</option>
                            </x-forms.select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <x-forms.text :fieldLabel="__('app.latitude')" fieldName="latitude" fieldId="latitude" :fieldValue="$follow->latitude" />
                </div>
                <div class="col-md-6">
                    <x-forms.text :fieldLabel="__('app.longitude')" fieldName="longitude" fieldId="longitude" :fieldValue="$follow->longitude" />
                </div>
                <div class="col-md-12">
                    <div class="form-group my-3">
                        <x-forms.textarea :fieldLabel="__('modules.lead.remark')" fieldName="remark" fieldId="remark" :fieldValue="$follow->remark"></x-forms.textarea>
                    </div>
                </div>
            </div>
        </div>
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

    datepicker('#next_follow_up_date', {
        position: 'bl',
        ...datepickerConfig
    });

    $('#send_reminder').change(function() {
        $('.send_reminder_div').toggleClass('d-none');
    });

    $('#save-lead-followup').click(function() {
        $.easyAjax({
            url: "{{ route('lead-contact.follow_up_update') }}",
            container: '#leadFollowUpForm',
            type: "POST",
            blockUI: true,
            data: $('#leadFollowUpForm').serialize(),
            success: function(response) {
                if (response.status === "success") {
                    window.location.href = "{{ route('lead-contact.show', $follow->lead_id) }}?tab=follow-up";
                }
            }
        });
    });
</script>
