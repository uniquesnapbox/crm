@php
$viewLeadCategoryPermission = user()->permission('view_lead_category');
$viewLeadSourcesPermission = user()->permission('view_lead_sources');
$addLeadSourcesPermission = user()->permission('add_lead_sources');
$addLeadCategoryPermission = user()->permission('add_lead_category');
$addProductPermission = user()->permission('add_product');
$addEmployeePermission = user()->permission('add_employees');
$addPermission = user()->permission('add_lead'); // For Added By field
$assignLeadPermission = in_array('admin', user_roles()) || user()->permission('add_lead') == 'all' || user()->permission('edit_lead') == 'all';
$rawEditMobile = preg_replace('/\D+/', '', (string) ($leadContact->mobile ?? ''));
$editMobileLocal = (str_starts_with($rawEditMobile, '91') && strlen($rawEditMobile) === 12) ? substr($rawEditMobile, 2) : substr($rawEditMobile, -10);
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<style>
    .lead-contact-form-shell {
        --lead-border: #d9e4f3;
        --lead-shadow: 0 16px 36px rgba(22, 44, 87, 0.08);
    }

    .lead-contact-form-shell.add-client {
        border: 1px solid var(--lead-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(22, 44, 87, 0.07);
    }

    .lead-contact-form-shell .lead-contact-section-title {
        margin-bottom: 0;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.01em;
        color: #1d2b43;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .lead-contact-form-shell .lead-contact-grid {
        margin-left: -8px;
        margin-right: -8px;
        padding: 12px 14px 14px;
    }

    .lead-contact-form-shell .lead-contact-grid .form-group,
    .lead-contact-form-shell .lead-contact-grid .input-group,
    .lead-contact-form-shell .lead-contact-grid .btn,
    .lead-contact-form-shell .lead-contact-grid .bootstrap-select {
        margin-bottom: 0;
    }

    .lead-contact-form-shell .lead-contact-grid > [class*="col-"] {
        padding-left: 8px;
        padding-right: 8px;
        margin-bottom: 0;
    }

    .lead-contact-form-shell .lead-contact-grid .form-group.my-3,
    .lead-contact-form-shell .lead-contact-grid .my-3 {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .lead-contact-form-shell .lead-contact-grid .mt-3 {
        margin-top: 0 !important;
    }

    .lead-contact-form-shell .lead-contact-grid .mb-12 {
        margin-bottom: 2px !important;
    }

    .lead-contact-form-shell .lead-contact-grid label,
    .lead-contact-form-shell .lead-contact-grid .f-14.text-dark-grey.mb-12 {
        font-size: 11px;
        font-weight: 600;
        color: #50627d;
        margin-bottom: 4px !important;
    }

    .lead-contact-form-shell .lead-contact-grid .form-control,
    .lead-contact-form-shell .lead-contact-grid .bootstrap-select > .dropdown-toggle {
        min-height: 34px;
        border-radius: 8px;
        border-color: #d5dfec;
        box-shadow: none;
    }

    .lead-contact-form-shell .lead-contact-grid .bootstrap-select > .dropdown-toggle {
        padding-top: 6px;
        padding-bottom: 6px;
    }

    .lead-contact-form-shell .lead-contact-grid .input-group-text {
        min-height: 34px;
        border-radius: 8px;
    }

    .lead-contact-form-shell .lead-contact-grid textarea.form-control {
        min-height: 76px;
    }

    .lead-contact-form-shell .lead-contact-grid .btn {
        border-radius: 8px;
    }

    .lead-contact-form-shell .lead-contact-grid .btn-outline-secondary {
        min-height: 34px;
        padding-top: 0.28rem;
        padding-bottom: 0.28rem;
        font-size: 12px;
    }

    .lead-contact-form-shell .lead-contact-grid .text-muted {
        font-size: 11px;
    }

    .lead-contact-form-shell .lead-contact-inline-label {
        margin-bottom: 0.1rem !important;
    }

    .lead-contact-form-shell #other-details,
    .lead-contact-form-shell .lead-contact-qualification {
        padding-top: 0;
    }

    .lead-contact-form-shell #other-details .col-md-12 .form-group {
        margin-bottom: 0 !important;
    }

    .lead-contact-form-shell #other-details .form-group .d-flex {
        margin-bottom: 2px;
    }

    .lead-contact-form-shell #other-details textarea.form-control {
        margin-top: 0;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-lead-data-form" method="PUT">
            <div class="add-client bg-white rounded lead-contact-form-shell">
                <h4 class="lead-contact-section-title border-bottom-grey">
                    @lang('modules.leadContact.leadDetails')</h4>

                <div class="row lead-contact-grid">

                    <div class="col-lg-4 col-md-6">
                        <x-forms.text :fieldLabel="__('app.name')" fieldName="client_name"
                            fieldId="client_name" fieldPlaceholder="" fieldRequired="true" field-label-inside="true"
                            :fieldValue="$leadContact->client_name" />
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.email fieldId="client_email" :fieldLabel="__('app.email')"
                            fieldName="client_email" :fieldPlaceholder="__('placeholders.email')"
                            :fieldValue="$leadContact->client_email" field-label-inside="true">
                        </x-forms.email>
                    </div>

                    @if ($viewLeadSourcesPermission != 'none')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="source_id" :fieldLabel="__('modules.lead.leadSource')"
                                fieldName="source_id" search="true" field-label-inside="true">
                                <option value="">--</option>
                                @foreach ($sources as $source)
                                    <option @if ($leadContact->source_id == $source->id) selected @endif value="{{ $source->id }}">
                                        {{ $source->type }}</option>
                                @endforeach

                                @if ($addLeadSourcesPermission == 'all' || $addLeadSourcesPermission == 'added')
                                    <x-slot name="append">
                                        <button type="button"
                                            class="btn btn-outline-secondary border-grey btn-sm px-3 add-lead-source"
                                            data-toggle="tooltip" data-original-title="{{ __('app.add').' '.__('modules.lead.leadSource') }}">@lang('app.add')</button>
                                    </x-slot>
                                @endif
                            </x-forms.select>
                        </div>
                    @endif

                    <div class="col-lg-4 col-md-6">
                        <x-forms.select fieldId="status_id" fieldLabel="Lead Status" fieldName="status_id" search="true" field-label-inside="true">
                            <option value="">--</option>
                            @foreach ($status as $item)
                                @php
                                    $statusType = trim((string) $item->type);
                                    $isCallNotConnected = strcasecmp($statusType, 'call not connected') === 0 || strcasecmp($statusType, 'call not conected') === 0;
                                    $displayStatusType = $isCallNotConnected ? 'CALL NOT CONNECTED' : $statusType;
                                    $displayStatusHtml = $isCallNotConnected
                                        ? '<span style="font-weight:900;text-transform:uppercase;letter-spacing:0.03em;">CALL NOT CONNECTED</span>'
                                        : e($displayStatusType);
                                @endphp
                                <option
                                    @selected($leadContact->status_id == $item->id)
                                    value="{{ $item->id }}"
                                    data-content="<span><i class='fa fa-circle mr-2' style='color: {{ $item->label_color }}'></i>{!! $displayStatusHtml !!}</span>">
                                    {!! $displayStatusHtml !!}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    @if ($addPermission == 'all')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="added_by" :fieldLabel="__('app.added').' '.__('app.by')"
                                fieldName="added_by" search="true" field-label-inside="true">
                                <option value="">--</option>
                                @foreach ($employees as $item)
                                    <x-user-option :user="$item" :selected="$leadContact->added_by == $item->id" />
                                @endforeach
                            </x-forms.select>
                        </div>
                    @endif

                    @if ($assignLeadPermission)
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="assigned_to" :fieldLabel="__('modules.tasks.assignTo')"
                                fieldName="assigned_to" search="true" field-label-inside="true">
                                <option value="">--</option>
                                @foreach ($employees as $item)
                                    <x-user-option :user="$item" :selected="$leadContact->assigned_to == $item->id" />
                                @endforeach

                                @if ($addEmployeePermission == 'all' || $addEmployeePermission == 'added')
                                    <x-slot name="append">
                                        <button id="add-employee" type="button"
                                                class="btn btn-outline-secondary border-grey btn-sm px-3"
                                                data-toggle="tooltip"
                                                data-original-title="{{ __('modules.employees.addNewEmployee') }}">@lang('app.add')</button>
                                    </x-slot>
                                @endif
                            </x-forms.select>
                        </div>
                    @endif

                </div>

                <h4 class="lead-contact-section-title border-top-grey">
                    @lang('modules.lead.companyDetails')</h4>

                <div class="row lead-contact-grid">

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.lead.companyName')" fieldName="company_name"
                            fieldId="company_name" :fieldPlaceholder="__('placeholders.company')"
                            :fieldValue="$leadContact->company_name" field-label-inside="true" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.lead.website')" fieldName="website" fieldId="website"
                            :fieldPlaceholder="__('placeholders.website')" :fieldValue="$leadContact->website" field-label-inside="true" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="form-group my-3">
                            <label class="f-14 text-dark-grey mb-12" for="mobile_local">
                                WhatsApp Mobile <sup class="f-14 mr-1">*</sup>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <select class="form-control" id="mobile_country_code" style="width: 90px; min-width: 90px; max-width: 90px;">
                                        @foreach ($countries as $item)
                                            @php $code = preg_replace('/\D+/', '', (string) $item->phonecode); @endphp
                                            @if (!empty($code))
                                                <option value="{{ $code }}" data-country="{{ $item->nicename }}"
                                                    @if ($leadContact->country == $item->nicename) selected @elseif (!$leadContact->country && $item->nicename == 'India') selected @endif>
                                                    +{{ $code }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <input type="text" class="form-control" id="mobile_local" maxlength="10"
                                    inputmode="numeric" pattern="[0-9]{10}" placeholder="9876543210"
                                    value="{{ $editMobileLocal }}" autocomplete="off">
                            </div>
                            <input type="hidden" name="mobile" id="mobile" value="">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.client.officePhoneNumber')" fieldName="office"
                            fieldId="office" fieldPlaceholder="" :fieldValue="$leadContact->office" field-label-inside="true" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.select fieldId="country" :fieldLabel="__('app.country')" fieldName="country"
                            search="true" field-label-inside="true">
                            <option value="">--</option>
                            @foreach ($countries as $item)
                                <option @if ($leadContact->country == $item->nicename) selected @elseif (!$leadContact->country && $item->nicename == 'India') selected @endif
                                    data-tokens="{{ $item->iso3 }}"
                                    data-phonecode="{{ $item->phonecode }}"
                                    data-content="<span class='flag-icon flag-icon-{{ strtolower($item->iso) }} flag-icon-squared'></span> {{ $item->nicename }}"
                                    value="{{ $item->nicename }}">{{ $item->nicename }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    {{-- Removed state, city, postal_code fields as per task --}}

                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="f-14 text-dark-grey mb-12" data-label="true" for="address">
                                    @lang('app.address')
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="fetch-address">
                                    <i class="fa fa-map-marker-alt"></i> Fetch Address
                                </button>
                            </div>
                            <textarea class="form-control" name="address" id="address" rows="3" placeholder="@lang('placeholders.address')">{{ $leadContact->address }}</textarea>
                        </div>
                    </div>

                </div>

                <h4 class="lead-contact-section-title border-top-grey lead-contact-qualification">
                    Lead Qualification
                </h4>

                <div class="row lead-contact-grid">
                    <div class="col-lg-4 col-md-6">
                        <x-forms.select fieldId="interest_level" fieldLabel="Interest Level" fieldName="interest_level" field-label-inside="true">
                            <option value="">--</option>
                            <option @selected($leadContact->interest_level === 'low') value="low" data-content="<span><i class='fa fa-circle mr-2' style='color:#64748b'></i>Low</span>">Low</option>
                            <option @selected($leadContact->interest_level === 'medium') value="medium" data-content="<span><i class='fa fa-circle mr-2' style='color:#2563eb'></i>Medium</span>">Medium</option>
                            <option @selected($leadContact->interest_level === 'high') value="high" data-content="<span><i class='fa fa-circle mr-2' style='color:#ea580c'></i>High</span>">High</option>
                            <option @selected($leadContact->interest_level === 'very_high') value="very_high" data-content="<span><i class='fa fa-circle mr-2' style='color:#16a34a'></i>Very High</span>">Very High</option>
                        </x-forms.select>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="form-group my-3">
                            <label class="f-14 text-dark-grey mb-12" for="deal_size">Deal Size</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="deal_size" id="deal_size"
                                value="{{ $leadContact->deal_size }}" placeholder="Expected amount">
                        </div>
                    </div>

                    @if ($viewLeadCategoryPermission != 'none')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="category_id" fieldLabel="Customer Group"
                                fieldName="category_id" search="true" field-label-inside="true">
                                <option value="">--</option>
                                @foreach ($categories as $category)
                                    <option @selected($leadContact->category_id == $category->id) value="{{ $category->id }}">
                                        {{ $category->category_name }}</option>
                                @endforeach

                                @if ($addLeadCategoryPermission == 'all' || $addLeadCategoryPermission == 'added')
                                    <x-slot name="append">
                                        <button type="button"
                                            class="btn btn-outline-secondary border-grey btn-sm px-3 add-lead-category"
                                            data-toggle="tooltip" data-original-title="{{ __('app.add').' Customer Group' }}">
                                            @lang('app.add')</button>
                                    </x-slot>
                                @endif
                            </x-forms.select>
                        </div>
                    @endif

                    <div class="col-lg-4 col-md-6">
                        <x-forms.select fieldId="contact_status" fieldLabel="Lead Contact Status" fieldName="contact_status" field-label-inside="true">
                            <option value="">--</option>
                            <option @selected($leadContact->contact_status === 'pending') value="pending">Pending</option>
                            <option @selected($leadContact->contact_status === 'connected') value="connected">Connected</option>
                            <option @selected($leadContact->contact_status === 'not_connected') value="not_connected">Not Connected</option>
                        </x-forms.select>
                    </div>

                    <div class="col-lg-8 col-md-6 {{ $leadContact->contact_status === 'not_connected' ? '' : 'd-none' }}" id="contact-status-reason-wrapper">
                        <div class="form-group my-3">
                            <label class="f-14 text-dark-grey mb-12" for="contact_status_reason">If Not Connected, Why?</label>
                            <textarea class="form-control" name="contact_status_reason" id="contact_status_reason" rows="3" placeholder="Write the reason">{{ $leadContact->contact_status_reason }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <label class="f-14 text-dark-grey mb-12" for="products_services">Products / Services</label>
                            <textarea class="form-control" name="products_services" id="products_services" rows="3" placeholder="What is the lead interested in?">{{ $leadContact->products_services }}</textarea>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <label class="f-14 text-dark-grey mb-12" for="note">Notes</label>
                            <textarea class="form-control" name="note" id="note" rows="4" placeholder="Qualification notes, comments, or background">{{ $leadContact->note }}</textarea>
                        </div>
                    </div>
                </div>

                <x-forms.custom-field :fields="$fields" :model="$leadContact"></x-forms.custom-field>

                <div class="row p-20">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary mr-2 mb-2" id="save-lead-form">@lang('app.save')</button>
                        <button type="button" class="btn btn-outline-secondary mr-2 mb-2" id="save-more-lead-form">@lang('app.saveAddMore')</button>
                        <a class="btn btn-cancel mb-2" href="{{ route('lead-contact.index') }}">@lang('app.cancel')</a>
                    </div>
                </div>

            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function() {
        function selectedCountryCode() {
            const code = ($('#mobile_country_code').val() || '91').toString().replace(/\D+/g, '');
            return code || '91';
        }

        function syncCountryToCode() {
            const selected = $('#country option:selected');
            const code = (selected.data('phonecode') || '91').toString().replace(/\D+/g, '') || '91';
            $('#mobile_country_code').val(code);
        }

        function syncCodeToCountry() {
            const code = selectedCountryCode();
            const $country = $('#country');
            const $match = $country.find('option').filter(function() {
                return (($(this).data('phonecode') || '').toString().replace(/\D+/g, '')) === code;
            }).first();

            if ($match.length) {
                $country.val($match.val());
                if (typeof $country.selectpicker === 'function') {
                    $country.selectpicker('refresh');
                }
            }
        }

        function sanitizeMobileLocal(value, countryCode) {
            const digits = (value || '').toString().replace(/\D+/g, '');
            return countryCode === '91' ? digits.slice(0, 10) : digits.slice(0, 12);
        }

        function syncHiddenMobile() {
            const code = selectedCountryCode();
            const local = sanitizeMobileLocal($('#mobile_local').val(), code);
            $('#mobile_local').val(local);
            $('#mobile').val(local ? ('+' + code + local) : '');
            return local;
        }

        function validateMobileBeforeSave() {
            const code = selectedCountryCode();
            const local = syncHiddenMobile();

            const isValid = code === '91' ? local.length === 10 : (local.length >= 6 && local.length <= 12);

            if (!isValid) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(code === '91'
                        ? 'Please enter a valid 10-digit mobile number for India (+91).'
                        : 'Please enter a valid mobile number (6-12 digits) for selected country code.');
                } else {
                    alert(code === '91'
                        ? 'Please enter a valid 10-digit mobile number for India (+91).'
                        : 'Please enter a valid mobile number (6-12 digits) for selected country code.');
                }
                return false;
            }

            return true;
        }

        $('#mobile_local').on('input', function() {
            syncHiddenMobile();
        });

        $('#country').on('change', function() {
            syncCountryToCode();
            syncHiddenMobile();
        });

        $('#mobile_country_code').on('change', function() {
            syncCodeToCountry();
            syncHiddenMobile();
        });

        syncCountryToCode();
        syncHiddenMobile();

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        // Save button (normal save)
        $('#save-lead-form').click(function() {
            if (!validateMobileBeforeSave()) {
                return;
            }
            saveLead('normal');
        });

        // Save & Add More button
        $('#save-more-lead-form').click(function() {
            if (!validateMobileBeforeSave()) {
                return;
            }
            saveLead('add_more');
        });

        function saveLead(action) {
            var url = "{{ route('lead-contact.update', [$leadContact->id]) }}";
            var data = $('#save-lead-data-form').serialize();
            if (action === 'add_more') {
                url += '?add_more=true';
                data += '&add_more=true';
            }

            $.easyAjax({
                url: url,
                container: '#save-lead-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                file: true,
                buttonSelector: action === 'add_more' ? "#save-more-lead-form" : "#save-lead-form",
                data: data,
                success: function(response) {
                    if (action === 'add_more') {
                        // Redirect to create form after save
                        window.location.href = "{{ route('lead-contact.create') }}";
                    } else {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        }

        $('body').on('click', '.add-lead-source', function() {
            const url = '{{ route('lead-source-settings.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.add-lead-category', function() {
            var url = '{{ route('leadCategory.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#create_task_category').click(function() {
            const url = "{{ route('taskCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#department-setting').click(function() {
            const url = "{{ route('departments.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#client_view_task').change(function() {
            $('#clientNotification').toggleClass('d-none');
        });

        $('#set_time_estimate').change(function() {
            $('#set-time-estimate-fields').toggleClass('d-none');
        });

        $('.toggle-other-details').click(function() {
            $(this).find('svg').toggleClass('fa-chevron-down fa-chevron-up');
            $('#other-details').toggleClass('d-none');
        });

        function toggleContactReason() {
            const showReason = $('#contact_status').val() === 'not_connected';
            $('#contact-status-reason-wrapper').toggleClass('d-none', !showReason);
        }

        $('#contact_status').on('change', toggleContactReason);
        toggleContactReason();

        $('#createTaskLabel').click(function() {
            const url = "{{ route('task-label.create') }}";
            $(MODAL_XL + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        $('#add-project').click(function() {
            $(MODAL_XL).modal('show');
            const url = "{{ route('projects.create') }}";
            $.easyAjax({
                url: url,
                blockUI: true,
                container: MODAL_XL,
                success: function(response) {
                    if (response.status == "success") {
                        $(MODAL_XL + ' .modal-body').html(response.html);
                        $(MODAL_XL + ' .modal-title').html(response.title);
                        init(MODAL_XL);
                    }
                }
            });
        });

        $('#add-employee').click(function() {
            $(MODAL_XL).modal('show');
            const url = "{{ route('employees.create') }}";
            $.easyAjax({
                url: url,
                blockUI: true,
                container: MODAL_XL,
                success: function(response) {
                    if (response.status == "success") {
                        $(MODAL_XL + ' .modal-body').html(response.html);
                        $(MODAL_XL + ' .modal-title').html(response.title);
                        init(MODAL_XL);
                    }
                }
            });
        });

        <x-forms.custom-field-filejs/>

        init(RIGHT_MODAL);
    });

    function checkboxChange(parentClass, id){
        let checkedData = '';
        $('.'+parentClass).find("input[type= 'checkbox']:checked").each(function () {
            checkedData = (checkedData !== '') ? checkedData+', '+$(this).val() : $(this).val();
        });
        $('#'+id).val(checkedData);
    }

    // Fetch address using geolocation
    $('body').on('click', '#fetch-address', function() {
        var button = $(this);
        var originalText = button.html();
        button.html('<i class="fa fa-spinner fa-spin"></i> Fetching...');
        button.prop('disabled', true);

        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;
                var url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`;

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        if (response && response.display_name) {
                            $('#address').val(response.display_name);
                        } else {
                            alert('Could not fetch the address from coordinates.');
                        }
                    },
                    error: function() {
                        alert('Error occurred while fetching the address.');
                    },
                    complete: function() {
                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            }, function(error) {
                alert('Geolocation failed: ' + error.message);
                button.html(originalText);
                button.prop('disabled', false);
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            alert("Geolocation is not supported by this browser.");
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
</script>
