@php
$viewLeadCategoryPermission = user()->permission('view_lead_category');
$viewLeadSourcesPermission = user()->permission('view_lead_sources');
$addLeadSourcesPermission = user()->permission('add_lead_sources');
$addLeadCategoryPermission = user()->permission('add_lead_category');
$addProductPermission = user()->permission('add_product');
$assignLeadPermission = in_array('admin', user_roles()) || user()->permission('add_lead') == 'all' || user()->permission('edit_lead') == 'all';
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-lead-data-form" >
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('modules.leadContact.leadDetails')</h4>
                <div class="row p-20">

                    <div class="col-lg-4 col-md-6">
                        <x-forms.text :fieldLabel="__('app.name')" fieldName="client_name"
                            fieldId="client_name" :fieldPlaceholder="__('placeholders.name')" fieldRequired="true" />
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <x-forms.email fieldId="client_email" :fieldLabel="__('app.email')"
                            fieldName="client_email" :fieldPlaceholder="__('placeholders.email')" :fieldHelp="__('modules.lead.leadEmailInfo')">
                        </x-forms.email>
                    </div>

                    @if ($viewLeadSourcesPermission != 'none')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.label class="my-3" fieldId="source_id" :fieldLabel="__('modules.lead.leadSource')">
                            </x-forms.label>
                            <x-forms.input-group>
                                <select class="form-control select-picker" name="source_id" id="source_id"
                                    data-live-search="true">
                                    <option value="">--</option>
                                    @foreach ($sources as $source)
                                        <option value="{{ $source->id }}">{{ $source->type }}</option>
                                    @endforeach
                                </select>

                                @if ($addLeadSourcesPermission == 'all' || $addLeadSourcesPermission == 'added')
                                    <x-slot name="append">
                                        <button type="button"
                                            class="btn btn-outline-secondary border-grey add-lead-source"
                                            data-toggle="tooltip" data-original-title="{{ __('app.add').' '.__('modules.lead.leadSource') }}">
                                            @lang('app.add')</button>
                                    </x-slot>
                                @endif
                            </x-forms.input-group>
                        </div>
                    @endif

                    @if ($addPermission == 'all')
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select fieldId="added_by" :fieldLabel="__('app.added').' '.__('app.by')"
                                fieldName="added_by">
                                <option value="">--</option>
                                @foreach ($employees as $item)
                                    <x-user-option :user="$item" :selected="user()->id == $item->id" />
                                @endforeach
                            </x-forms.select>
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

                </div>

                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-top-grey">
                    <a href="javascript:;" class="text-dark toggle-other-details"><i class="fa fa-chevron-down"></i>
                        @lang('modules.client.companyDetails')</a>
                </h4>

                <div class="row p-20 d-none" id="other-details">

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.lead.companyName')" fieldName="company_name"
                            fieldId="company_name" :fieldPlaceholder="__('placeholders.company')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.lead.website')" fieldName="website" fieldId="website"
                            :fieldPlaceholder="__('placeholders.website')" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.tel fieldId="mobile" fieldLabel="WhatsApp Mobile" fieldName="mobile"
                           :fieldPlaceholder="__('placeholders.mobile')" fieldRequired="true"></x-forms.tel>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.text :fieldLabel="__('modules.client.officePhoneNumber')" fieldName="office"
                            fieldId="office" fieldPlaceholder="" />
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <x-forms.select fieldId="country" :fieldLabel="__('app.country')" fieldName="country"
                            search="true">
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
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="fetch-address">
                                    <i class="fa fa-map-marker-alt"></i> Fetch Address
                                </button>
                            </div>
                            <textarea class="form-control" name="address" id="address" rows="3" placeholder="@lang('placeholders.address')"></textarea>
                        </div>
                    </div>

                    <x-forms.custom-field :fields="$fields" class="col-md-12"></x-forms.custom-field>

                </div>

                {{-- Dropdown button for actions --}}
                <div class="row p-20">
                    <div class="col-md-12">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                @lang('app.actions')
                            </button>
                            <div class="dropdown-menu">
                                <button type="button" class="dropdown-item" id="save-lead-form">@lang('app.save')</button>
                                <button type="button" class="dropdown-item" id="save-more-lead-form">@lang('app.saveAddMore')</button>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('lead-contact.index') }}">@lang('app.cancel')</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function() {

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        $('#save-more-lead-form').click(function () {
            $('#add_more').val(true);
            const url = "{{ route('lead-contact.store') }}?add_more=true";
            var data = $('#save-lead-data-form').serialize() + '&add_more=true';
            saveLead(data, url, "#save-more-lead-form");
        });

        $('#save-lead-form').click(function() {
            const url = "{{ route('lead-contact.store') }}";
            var data = $('#save-lead-data-form').serialize();
            saveLead(data, url, "#save-lead-form");
        });

        function saveLead(data, url, buttonSelector) {
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
                    if(response.add_more == true) {
                        var right_modal_content = $.trim($(RIGHT_MODAL_CONTENT).html());
                        if(right_modal_content.length) {
                            $(RIGHT_MODAL_CONTENT).html(response.html.html);
                            $('#add_more').val(false);
                        }
                        else {
                            $('.content-wrapper').html(response.html.html);
                            init('.content-wrapper');
                            $('#add_more').val(false);
                        }
                    }
                    else {
                        window.location.href = response.redirectUrl;
                    }

                    if (typeof showTable !== 'undefined' && typeof showTable === 'function') {
                        showTable();
                    }
                }
            });
        }

        $('body').on('click', '.add-lead-source', function() {
            var url = '{{ route('lead-source-settings.create') }}';
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('.toggle-other-details').click(function() {
            $(this).find('svg').toggleClass('fa-chevron-down fa-chevron-up');
            $('#other-details').toggleClass('d-none');
        });

        init(RIGHT_MODAL);

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

    });

    function checkboxChange(parentClass, id){
        var checkedData = '';
        $('.'+parentClass).find("input[type= 'checkbox']:checked").each(function () {
            checkedData = (checkedData !== '') ? checkedData+', '+$(this).val() : $(this).val();
        });
        $('#'+id).val(checkedData);
    }
</script>
