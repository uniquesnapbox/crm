@php
    $changeEmployeeRolePermission = user()->permission('change_employee_role');
    $selectedCountry = collect($countries)->firstWhere('id', $employee->country_id);
    $defaultCountry = collect($countries)->first(function ($item) {
        return strtolower($item->iso) === 'in';
    });
    $countryId = $selectedCountry?->id ?: $defaultCountry?->id;
    $countryPhoneCode = $employee->country_phonecode ?: $selectedCountry?->phonecode ?: $defaultCountry?->phonecode;
    $employeeType = $employee->employeeDetail->employee_type ?: 'office_staff';
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/tagify.css') }}">
<style>
    .tagify_tags .height-35 {
        height: auto !important;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-data-form" method="PUT">
            <div class="bg-white rounded add-client">
                <h4 class="p-20 mb-0 f-21 font-weight-normal border-bottom-grey">@lang('modules.employees.accountDetails')</h4>

                <div class="p-20 row">
                    <input type="hidden" name="employee_id" value="{{ $employee->employeeDetail->employee_id }}">
                    <input type="hidden" name="country_phonecode" id="country_phonecode" value="{{ $countryPhoneCode }}">
                    <input type="hidden" name="latitude" id="latitude" value="{{ $employee->employeeDetail->latitude }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ $employee->employeeDetail->longitude }}">

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text fieldId="name" :fieldLabel="__('modules.employees.employeeName')" fieldName="name"
                                      :fieldValue="$employee->name" fieldRequired="true" :fieldPlaceholder="__('placeholders.name')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text fieldId="email" :fieldLabel="__('modules.employees.employeeEmail')" fieldName="email"
                                      :fieldValue="$employee->email" fieldRequired="true" :fieldPlaceholder="__('placeholders.email')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text fieldId="mobile" :fieldLabel="__('app.mobile')" fieldName="mobile"
                                      :fieldValue="$employee->mobile" fieldRequired="true" :fieldPlaceholder="__('placeholders.mobile')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.datepicker fieldId="date_of_birth" :fieldLabel="__('modules.employees.dateOfBirth')"
                                            fieldName="date_of_birth" fieldRequired="true"
                                            :fieldValue="($employee->employeeDetail->date_of_birth ? $employee->employeeDetail->date_of_birth->format(company()->date_format) : '')"
                                            :fieldPlaceholder="__('placeholders.date')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.label class="mt-3" fieldId="password" :fieldLabel="__('app.password')" />
                        <x-forms.input-group>
                            <input type="password" name="password" id="password" autocomplete="off" class="form-control height-35 f-14">
                            <x-slot name="preappend">
                                <button type="button" data-toggle="tooltip" data-original-title="@lang('app.viewPassword')"
                                        class="btn btn-outline-secondary border-grey height-35 toggle-password">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </x-slot>
                            <x-slot name="append">
                                <button id="random_password" type="button" data-toggle="tooltip"
                                        data-original-title="@lang('modules.client.generateRandomPassword')"
                                        class="btn btn-outline-secondary border-grey height-35">
                                    <i class="fa fa-random"></i>
                                </button>
                            </x-slot>
                        </x-forms.input-group>
                        <small class="form-text text-muted">@lang('modules.client.passwordUpdateNote')</small>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.label class="my-3" fieldId="employee_designation" :fieldLabel="__('app.designation')" fieldRequired="true" />
                        <select class="form-control select-picker" name="designation" id="employee_designation" data-live-search="true">
                            <option value="">--</option>
                            @foreach ($designations as $designation)
                                <option @selected($employee->employeeDetail->designation_id == $designation->id) value="{{ $designation->id }}">
                                    {{ $designation->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.label class="my-3" fieldId="employee_department" :fieldLabel="__('app.department')" fieldRequired="true" />
                        <select class="form-control select-picker" name="department" id="employee_department" data-live-search="true">
                            <option value="">--</option>
                            @foreach ($teams as $team)
                                <option @selected($employee->employeeDetail->department_id == $team->id) value="{{ $team->id }}">
                                    {{ $team->team_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.datepicker fieldId="joining_date" :fieldLabel="__('modules.employees.joiningDate')"
                                            fieldName="joining_date" fieldRequired="true"
                                            :fieldValue="$employee->employeeDetail->joining_date->format(company()->date_format)"
                                            :fieldPlaceholder="__('placeholders.date')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.select fieldId="country" :fieldLabel="__('app.country')" fieldName="country" search="true">
                            <option value="">--</option>
                            @foreach ($countries as $item)
                                <option data-phonecode="{{ $item->phonecode }}"
                                        data-tokens="{{ $item->iso3 }}"
                                        data-content="<span class='flag-icon flag-icon-{{ strtolower($item->iso) }} flag-icon-squared'></span> {{ $item->nicename }}"
                                        value="{{ $item->id }}"
                                    {{ $countryId == $item->id ? 'selected' : '' }}>
                                    {{ $item->nicename }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.select fieldId="reporting_to" :fieldLabel="__('modules.employees.reportingTo')" fieldName="reporting_to" search="true">
                            <option value="">--</option>
                            @foreach ($employees as $item)
                                <x-user-option :user="$item" :selected="$employee->employeeDetail->reporting_to == $item->id" />
                            @endforeach
                        </x-forms.select>
                    </div>

                    @if (
                        ((in_array('admin', $userRoles) && in_array('admin', user_roles())) || (!in_array('admin', $userRoles)))
                        && $employee->id != user()->id
                        && $changeEmployeeRolePermission == 'all'
                    )
                        <div class="col-lg-3 col-md-6">
                            <x-forms.select fieldId="role" :fieldLabel="__('app.role')" fieldName="role">
                                @foreach ($roles as $role)
                                    <option
                                        @if (
                                            (in_array($role->name, $userRoles) && $role->name == 'admin')
                                            || (in_array($role->name, $userRoles) && !in_array('admin', $userRoles))
                                        )
                                            selected
                                        @endif
                                        value="{{ $role->id }}">{{ $role->display_name }}
                                    </option>
                                @endforeach
                            </x-forms.select>
                        </div>
                    @endif

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text fieldId="website" :fieldLabel="__('modules.client.website')" fieldName="website"
                                      :fieldValue="$employee->employeeDetail->website" :fieldPlaceholder="__('placeholders.website')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text fieldId="office_phone" :fieldLabel="__('modules.client.officePhoneNumber')" fieldName="office_phone"
                                      :fieldValue="$employee->employeeDetail->office_phone" :fieldPlaceholder="__('placeholders.mobile')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.label class="my-3" fieldId="hourly_rate" :fieldLabel="__('modules.employees.hourlyRate')" />
                        <x-forms.input-group>
                            <x-slot name="prepend">
                                <span class="input-group-text f-14 bg-white-shade">{{ company()->currency->currency_symbol }}</span>
                            </x-slot>
                            <input type="number" step=".01" min="0" class="form-control height-35 f-14"
                                   name="hourly_rate" id="hourly_rate" value="{{ $employee->employeeDetail->hourly_rate ?? '' }}">
                        </x-forms.input-group>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.number fieldId="notice_period" fieldName="notice_period" :fieldLabel="__('modules.employees.noticePeriod')"
                                        :fieldValue="$employee->employeeDetail->notice_period" minValue="0" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.file allowedFileExtensions="png jpg jpeg svg bmp" class="mr-0 mr-lg-2 mr-md-2 cropper"
                                      :fieldLabel="__('modules.profile.profilePicture')"
                                      :fieldValue="($employee->image ? $employee->masked_image_url : $employee->image_url)"
                                      fieldName="image" fieldId="image" fieldHeight="90"
                                      :popover="__('messages.fileFormat.ImageFile')" />
                    </div>

                    <div class="col-md-12 mt-2">
                        <x-forms.text class="tagify_tags" fieldId="tags" :fieldLabel="__('app.skills')" fieldName="tags"
                                      :fieldPlaceholder="__('placeholders.skills')" :fieldValue="implode(',', $employee->skills())" />
                    </div>
                </div>

                <h4 class="p-20 mb-0 f-18 font-weight-normal border-top-grey">Attendance Setup</h4>
                <div class="p-20 row">
                    <div class="col-lg-4 col-md-6">
                        <x-forms.select fieldId="employee_type" fieldName="employee_type" fieldLabel="Employee Type" fieldRequired="true">
                            <option value="office_staff" @selected($employeeType === 'office_staff')>Office Staff (Geofence Required)</option>
                            <option value="sales_staff" @selected($employeeType === 'sales_staff')>Sales Staff (Anywhere Clock-in)</option>
                        </x-forms.select>
                    </div>

                    <div class="col-lg-4 col-md-6 geofence-field">
                        <x-forms.text fieldId="office_latitude" fieldName="office_latitude" fieldLabel="Office Latitude"
                                      :fieldValue="$employee->employeeDetail->office_latitude" :fieldPlaceholder="__('placeholders.latitude')" />
                    </div>

                    <div class="col-lg-4 col-md-6 geofence-field">
                        <x-forms.text fieldId="office_longitude" fieldName="office_longitude" fieldLabel="Office Longitude"
                                      :fieldValue="$employee->employeeDetail->office_longitude" :fieldPlaceholder="__('placeholders.longitude')" />
                    </div>

                    <div class="col-lg-4 col-md-6 geofence-field">
                        <x-forms.number fieldId="allowed_radius" fieldName="allowed_radius" fieldLabel="Allowed Radius (meters)"
                                        minValue="1" :fieldValue="$employee->employeeDetail->allowed_radius" :fieldPlaceholder="'e.g. 200'" />
                    </div>

                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="f-14 text-dark-grey mb-12" for="address">@lang('app.address') <sup class="f-14 mr-1">*</sup></label>
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="fetch-location">
                                    <i class="fa fa-map-marker-alt"></i> Fetch Location
                                </button>
                            </div>
                            <textarea class="form-control" name="address" id="address" rows="2"
                                      placeholder="@lang('placeholders.address')">{{ $employee->employeeDetail->address }}</textarea>
                            <small class="text-muted d-block mt-2" id="location-captured-text"></small>
                        </div>
                    </div>
                </div>

                <x-forms.custom-field :fields="$fields" :model="$employeeDetail"></x-forms.custom-field>

                <x-form-actions>
                    <x-forms.button-primary id="save-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
                    <x-forms.button-cancel :link="route('employees.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script src="{{ asset('vendor/jquery/tagify.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, { position: 'bl', ...datepickerConfig });
        });

        datepicker('#joining_date', {
            position: 'bl',
            @if (!is_null($employee->employeeDetail->joining_date))
            dateSelected: new Date("{{ str_replace('-', '/', $employee->employeeDetail->joining_date) }}"),
            @endif
            ...datepickerConfig
        });

        datepicker('#date_of_birth', {
            position: 'bl',
            maxDate: new Date(),
            @if (!is_null($employee->employeeDetail->date_of_birth))
            dateSelected: new Date("{{ str_replace('-', '/', $employee->employeeDetail->date_of_birth) }}"),
            @endif
            ...datepickerConfig
        });

        const input = document.querySelector('input[name=tags]');
        if (input) {
            new Tagify(input, { whitelist: {!! json_encode($skills) !!} });
        }

        function toggleGeofenceFields() {
            const employeeType = $('#employee_type').val();
            if (employeeType === 'sales_staff') {
                $('.geofence-field').addClass('d-none');
            } else {
                $('.geofence-field').removeClass('d-none');
            }
        }

        toggleGeofenceFields();
        $('#employee_type').on('change', toggleGeofenceFields);

        $('#country').on('change', function() {
            const phonecode = $(this).find(':selected').data('phonecode') || '';
            $('#country_phonecode').val(phonecode);
            $('.select-picker').selectpicker('refresh');
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

                if (!$('#office_latitude').val()) {
                    $('#office_latitude').val(lat);
                }

                if (!$('#office_longitude').val()) {
                    $('#office_longitude').val(lng);
                }

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

        $('#save-form').click(function() {
            const url = "{{ route('employees.update', $employee->id) }}";

            $.easyAjax({
                url: url,
                container: '#save-data-form',
                type: 'POST',
                disableButton: true,
                blockUI: true,
                buttonSelector: '#save-form',
                file: true,
                data: $('#save-data-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });

        <x-forms.custom-field-filejs/>

        $('#random_password').click(function() {
            const randPassword = Math.random().toString(36).substr(2, 8);
            $('#password').val(randPassword);
        });

        init(RIGHT_MODAL);
    });

    $('.cropper').on('dropify.fileReady', function() {
        const inputId = $(this).find('input').attr('id');
        let url = "{{ route('cropper', ':element') }}";
        url = url.replace(':element', inputId);
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });
</script>
