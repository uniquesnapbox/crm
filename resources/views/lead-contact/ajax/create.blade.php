@php
$viewLeadSourcesPermission = user()->permission('view_lead_sources');
$addLeadSourcesPermission = user()->permission('add_lead_sources');
$assignLeadPermission = in_array('admin', user_roles()) || user()->permission('add_lead') == 'all' || user()->permission('edit_lead') == 'all';
$addFollowUpPermission = user()->permission('add_lead_follow_up');
$canScheduleFollowUp = in_array($addFollowUpPermission, ['all', 'added']);
@endphp

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-lead-data-form">
            <input type="hidden" name="redirect_url" value="{{ request('redirect_url') }}">

            <div class="bg-white rounded p-20">
                <h4 class="mb-4 f-21 font-weight-normal text-capitalize border-bottom-grey pb-3">
                    @lang('modules.leadContact.leadDetails')
                </h4>

                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <x-forms.text :fieldLabel="__('app.name')" fieldName="client_name"
                                      fieldId="client_name" :fieldPlaceholder="__('placeholders.name')" fieldRequired="true" />
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.tel fieldId="mobile" fieldLabel="Phone / WhatsApp" fieldName="mobile"
                                     :fieldPlaceholder="__('placeholders.mobile')" fieldRequired="true"></x-forms.tel>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.email fieldId="client_email" :fieldLabel="__('app.email')"
                                       fieldName="client_email" :fieldPlaceholder="__('placeholders.email')">
                        </x-forms.email>
                    </div>

                    @if ($viewLeadSourcesPermission != 'none')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.label class="my-3" fieldId="source_id" :fieldLabel="__('modules.lead.leadSource')"></x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="source_id" id="source_id" data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source->id }}">{{ $source->type }}</option>
                                    @endforeach
                                </select>

                                @if ($addLeadSourcesPermission == 'all' || $addLeadSourcesPermission == 'added')
                                    <x-slot name="append">
                                        <button type="button"
                                                class="btn btn-outline-secondary border-grey add-lead-source"
                                                data-toggle="tooltip"
                                                data-original-title="{{ __('app.add') . ' ' . __('modules.lead.leadSource') }}">
                                            @lang('app.add')
                                        </button>
                                    </x-slot>
                                @endif
                            </x-forms.input-group>
                        </div>
                    @endif

                    @if ($assignLeadPermission)
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="assigned_to" :fieldLabel="__('modules.tasks.assignTo')"
                                            fieldName="assigned_to" search="true">
                                <option value="">--</option>
                                @foreach ($employees as $item)
                                    <x-user-option :user="$item" />
                                @endforeach
                            </x-forms.select>
                        </div>
                    @endif

                    @if ($addPermission == 'all')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="added_by" :fieldLabel="__('app.added') . ' ' . __('app.by')"
                                            fieldName="added_by">
                                <option value="">--</option>
                                @foreach ($employees as $item)
                                    <x-user-option :user="$item" :selected="user()->id == $item->id" />
                                @endforeach
                            </x-forms.select>
                        </div>
                    @endif
                </div>

                <div class="row mt-3 border-top-grey pt-3">
                    <div class="col-lg-4 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.lead.website')" fieldName="website" fieldId="website"
                                      :fieldPlaceholder="__('placeholders.website')" />
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.client.officePhoneNumber')" fieldName="office"
                                      fieldId="office" fieldPlaceholder="" />
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.select fieldId="country" :fieldLabel="__('app.country')" fieldName="country" search="true">
                            <option value="">--</option>
                            @foreach ($countries as $item)
                                <option data-tokens="{{ $item->iso3 }}"
                                        data-content="<span class='flag-icon flag-icon-{{ strtolower($item->iso) }} flag-icon-squared'></span> {{ $item->nicename }}"
                                        value="{{ $item->nicename }}"
                                    {{ $item->nicename == 'India' ? 'selected' : '' }}>
                                    {{ $item->nicename }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="f-14 text-dark-grey mb-12" data-label="true" for="address">
                                    @lang('app.address')
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="fetch-location">
                                    <i class="fa fa-map-marker-alt"></i> Fetch Location
                                </button>
                            </div>
                            <textarea class="form-control" name="address" id="address" rows="2"
                                      placeholder="@lang('placeholders.address')"></textarea>
                            <small class="text-muted d-block mt-2" id="location-captured-text"></small>
                        </div>
                    </div>

                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                </div>

                <div class="row mt-2 border-top-grey pt-3">
                    <div class="col-md-12">
                        <h5 class="mb-3 f-16 font-weight-normal">Follow-up (Quick Add)</h5>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.datepicker fieldId="followup_date"
                                            :fieldLabel="__('modules.lead.leadFollowUp')"
                                            fieldName="followup_date"
                                            :fieldPlaceholder="__('placeholders.date')" />
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="bootstrap-timepicker timepicker">
                            <x-forms.text :fieldLabel="__('modules.timeLogs.startTime')" fieldName="reminder_time"
                                          fieldId="reminder_time"
                                          :fieldValue="now(company()->timezone)->format(company()->time_format)" />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <x-forms.textarea fieldId="followup_note" :fieldLabel="__('modules.lead.remark')"
                                          fieldName="followup_note" :fieldPlaceholder="__('modules.lead.remark')">
                        </x-forms.textarea>
                    </div>
                </div>

                <x-forms.custom-field :fields="$fields" class="col-md-12 mt-2"></x-forms.custom-field>

                <div class="d-flex flex-wrap align-items-center mt-4">
                    <button type="button" class="btn btn-primary mr-2 mb-2" id="save-lead-form">
                        <i class="fa fa-check mr-1"></i> @lang('app.save')
                    </button>

                    <button type="button" class="btn btn-secondary mr-2 mb-2" id="save-more-lead-form">
                        <i class="fa fa-plus mr-1"></i> @lang('app.saveAddMore')
                    </button>

                    @if ($canScheduleFollowUp)
                        <button type="button" class="btn btn-info mr-2 mb-2" id="schedule-followup-lead-form">
                            <i class="fa fa-calendar-plus mr-1"></i> Save & Schedule Follow-up
                        </button>
                    @endif

                    <a class="btn btn-outline-secondary mb-2" href="{{ route('lead-contact.index') }}">
                        @lang('app.cancel')
                    </a>
                </div>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(".select-picker").selectpicker();

        const leadFollowUpDate = datepicker('#followup_date', {
            position: 'bl',
            ...datepickerConfig
        });

        $('#reminder_time').timepicker({
            @if (company()->time_format == 'H:i')
            showMeridian: false,
            @endif
        });

        $('#save-more-lead-form').click(function () {
            saveLead('save_new', '#save-more-lead-form');
        });

        $('#save-lead-form').click(function() {
            saveLead('save', '#save-lead-form');
        });

        $('#schedule-followup-lead-form').click(function() {
            saveLead('schedule_follow_up', '#schedule-followup-lead-form');
        });

        function saveLead(formAction, buttonSelector) {
            let url = "{{ route('lead-contact.store') }}";
            let data = $('#save-lead-data-form').serialize();

            if (formAction === 'save_new') {
                url += '?add_more=true';
                data += '&add_more=true';
            }

            data += '&form_action=' + formAction;

            $.easyAjax({
                url: url,
                container: '#save-lead-data-form',
                type: "POST",
                file: true,
                disableButton: true,
                blockUI: true,
                buttonSelector: buttonSelector,
                data: data,
                success: function(response) {
                    if (response.add_more === true) {
                        const rightModalContent = $.trim($(RIGHT_MODAL_CONTENT).html());

                        if (rightModalContent.length) {
                            $(RIGHT_MODAL_CONTENT).html(response.html.html);
                        } else {
                            $('.content-wrapper').html(response.html.html);
                            init('.content-wrapper');
                        }

                        return;
                    }

                    window.location.href = response.redirectUrl;
                }
            });
        }

        $('body').on('click', '.add-lead-source', function() {
            const url = '{{ route('lead-source-settings.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '#fetch-location', function() {
            const button = $(this);
            const originalText = button.html();

            button.html('<i class="fa fa-spinner fa-spin"></i> Fetching...');
            button.prop('disabled', true);

            if (!("geolocation" in navigator)) {
                button.html(originalText);
                button.prop('disabled', false);
                return;
            }

            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;

                $('#latitude').val(lat);
                $('#longitude').val(lng);
                $('#location-captured-text').text(`Coordinates captured: ${lat}, ${lng}`);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        if (response && response.display_name) {
                            $('#address').val(response.display_name);
                        }
                    },
                    complete: function() {
                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            }, function() {
                button.html(originalText);
                button.prop('disabled', false);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        });

        init(RIGHT_MODAL);
    });
</script>
