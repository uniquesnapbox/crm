<style>
    #myModal .modal-dialog.lead-followup-modal-dialog {
        max-width: 680px;
        width: calc(100% - 1rem);
    }

    #myModal .lead-followup-modal {
        font-size: 0.92rem;
    }

    #myModal .lead-followup-modal .modal-header,
    #myModal .lead-followup-modal .modal-body,
    #myModal .lead-followup-modal .modal-footer {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    #myModal .lead-followup-modal .modal-body {
        padding-top: 0.9rem;
        padding-bottom: 0.9rem;
    }

    #myModal .lead-followup-modal .form-group {
        margin-top: 0.85rem !important;
        margin-bottom: 0.85rem !important;
    }

    #myModal .lead-followup-modal .control-label,
    #myModal .lead-followup-modal label,
    #myModal .lead-followup-modal .text-dark-grey {
        font-size: 0.82rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        color: #64748b;
    }

    #myModal .lead-followup-modal input.form-control,
    #myModal .lead-followup-modal textarea.form-control {
        min-height: 38px;
        padding: 0.45rem 0.75rem;
        border-radius: 0.55rem;
    }

    #myModal .lead-followup-modal textarea.form-control {
        min-height: 96px;
    }

    #myModal .lead-followup-modal .btn-primary,
    #myModal .lead-followup-modal .btn-cancel,
    #myModal .lead-followup-modal .btn-secondary {
        border-radius: 0.55rem;
    }

    #myModal .lead-followup-modal .followup-photo-card {
        width: 136px;
        border: 1px solid #e2e8f0;
        border-radius: 0.7rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    #myModal .lead-followup-modal .followup-photo-card img {
        height: 92px !important;
        object-fit: contain;
        background: #fff;
        border-radius: 0.5rem;
    }

    #myModal .lead-followup-modal .followup-photo-name {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.2em;
        line-height: 1.1;
        font-size: 0.72rem;
        color: #475569;
        text-align: center;
    }

    #myModal .lead-followup-modal .lead-followup-meta {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.8rem;
        padding: 0.85rem 1rem;
    }
    #myModal .lead-followup-modal .lead-followup-meta .data-label {
        color: #64748b;
        font-size: 0.8rem;
    }
    #myModal .lead-followup-modal .lead-followup-meta .data-value {
        color: #0f172a;
        font-size: 0.92rem;
        font-weight: 600;
    }

    #myModal .lead-followup-modal .help-block,
    #myModal .lead-followup-modal .form-text {
        font-size: 0.78rem;
    }
</style>
<div class="modal-header">
    <h5 class="modal-title">@lang('modules.lead.addFollowUp')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>
<div class="modal-body lead-followup-modal">
    <x-form id="leadFollowUpForm" method="POST" class="ajax-form" enctype="multipart/form-data">
        <div class="form-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="lead-followup-meta mb-2">
                        <x-cards.data-row :label="__('modules.lead.clientName')" :value="$leadContact->client_name ?? '--'" />
                    </div>
                </div>
                <div class="col-md-6 pr-md-2">
                    <x-forms.datepicker fieldId="next_follow_up_date" fieldRequired="true"
                        :fieldLabel="__('modules.lead.leadFollowUp')" fieldName="next_follow_up_date"
                        :fieldValue="now(company()->timezone)->format(company()->date_format)"
                        :fieldPlaceholder="__('placeholders.date')" />
                </div>
                <div class="col-md-6 pl-md-2">
                    <div class="bootstrap-timepicker timepicker">
                        <x-forms.text :fieldLabel="__('modules.timeLogs.startTime')" fieldName="start_time"
                            fieldId="start_time" fieldRequired="true"
                            :fieldValue="now(company()->timezone)->format(company()->time_format)" />
                    </div>
                </div>
                <div class="col-lg-12 my-2">
                    <x-forms.checkbox :fieldLabel="__('modules.tasks.reminder')" fieldName="send_reminder"
                        fieldId="send_reminder" fieldValue="yes" />
                </div>
                <div class="col-lg-12 send_reminder_div d-none">
                    <div class="row">
                        <div class="col-lg-6 mt-1 pr-md-2">
                            <x-forms.number :fieldLabel="__('modules.events.remindBefore')" fieldName="remind_time"
                                fieldId="remind_time" fieldValue="" />
                        </div>
                        <div class="col-md-6 mt-3 pl-md-2">
                            <x-forms.select fieldId="remind_type" fieldLabel="" fieldName="remind_type" search="true">
                                <option value="day">@lang('app.day')</option>
                                <option value="hour">@lang('app.hour')</option>
                                <option value="minute">@lang('app.minute')</option>
                            </x-forms.select>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group my-2">
                        <x-forms.textarea :fieldLabel="__('modules.lead.remark')" fieldName="remark" fieldId="remark"></x-forms.textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group my-2">
                        <x-forms.label fieldId="attachments" fieldLabel="Attach Photos / Screenshots" />
                        <input type="file" class="form-control" name="attachments[]" id="attachments" multiple accept="image/*">
                        <small class="form-text text-muted mb-0">Selected photos will be sent with the 10 minute WhatsApp reminder.</small>
                        <div id="attachmentsPreview" class="d-flex flex-wrap mt-3" style="gap: 10px;"></div>
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

    function renderAttachmentPreview(input) {
        const previewBox = document.getElementById('attachmentsPreview');
        if (!previewBox) {
            return;
        }

        previewBox.innerHTML = '';

        if (!input.files || input.files.length === 0) {
            return;
        }

        Array.from(input.files).forEach((file) => {
            if (!file.type || !file.type.startsWith('image/')) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'followup-photo-card p-2';

            const img = document.createElement('img');
            img.className = 'img-fluid rounded';
            img.style.height = '92px';
            img.style.width = '100%';
            img.style.objectFit = 'cover';
            img.alt = file.name;

            const label = document.createElement('small');
            label.className = 'd-block mt-2 followup-photo-name';
            label.textContent = file.name;

            const objectUrl = URL.createObjectURL(file);
            img.src = objectUrl;
            img.onload = function() {
                URL.revokeObjectURL(objectUrl);
            };

            wrapper.appendChild(img);
            wrapper.appendChild(label);
            previewBox.appendChild(wrapper);
        });
    }

    $('#attachments').on('change', function() {
        renderAttachmentPreview(this);
    });

    const $leadFollowUpModal = $(MODAL_LG);
    $leadFollowUpModal.find('.modal-dialog').addClass('lead-followup-modal-dialog');
    $leadFollowUpModal.off('hidden.bs.modal.leadFollowUp').on('hidden.bs.modal.leadFollowUp', function() {
        $(this).find('.modal-dialog').removeClass('lead-followup-modal-dialog');
    });

    $('#save-lead-followup').click(function() {
        $.easyAjax({
            url: "{{ route('lead-contact.follow_up_store') }}",
            container: '#leadFollowUpForm',
            type: "POST",
            blockUI: true,
            file: true,
            success: function(response) {
                if (response.status === "success") {
                    const modalOpen = $(MODAL_LG).hasClass('show') || $(MODAL_LG).hasClass('in');
                    if (modalOpen) {
                        $(MODAL_LG).modal('hide');
                        window.location.href = "{{ route('lead-contact.show', $leadId) }}?tab=history";
                        return;
                    }
                    window.location.href = "{{ route('lead-contact.show', $leadId) }}?tab=history";
                }
            }
        });
    });
</script>
