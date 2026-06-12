@php
    $addPermission = user()->permission('add_clients');

    $indiaCountry = collect($countries)->first(function ($country) {
        return strtoupper($country->iso ?? '') === 'IN';
    });

    $leadCountry = null;

    if (isset($lead) && !empty($lead->country)) {
        $leadCountry = collect($countries)->first(function ($country) use ($lead) {
            return strtoupper($country->nicename ?? '') === strtoupper($lead->country ?? '');
        });
    }

    $defaultCountryId = old('country', optional($leadCountry)->id ?: optional($indiaCountry)->id);
    $defaultPhoneCode = old('country_phonecode', optional($leadCountry)->phonecode ?: optional($indiaCountry)->phonecode ?: 91);
@endphp

<style>
    .client-edit-tabs {
        border-bottom: 1px solid #e5e7eb;
        gap: 8px;
    }

    .client-edit-tabs .nav-link {
        border-radius: 8px;
        padding: 8px 14px;
        font-weight: 500;
        color: #4b5563;
    }

    .client-edit-tabs .nav-link.active {
        background: #e2430b;
        color: #fff;
    }

    .client-tab-pane {
        padding-top: 16px;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-client-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('app.add') @lang('app.client')
                </h4>

                @if (isset($lead->id))
                    <input type="hidden" name="lead" value="{{ $lead->id }}">
                @endif

                <ul class="nav nav-pills p-20 pb-0 client-edit-tabs" id="client-create-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-basic-link" data-toggle="tab" href="#tab-basic" role="tab">
                            Basic Info
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-company-link" data-toggle="tab" href="#tab-company" role="tab">
                            Company
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-followup-link" data-toggle="tab" href="#tab-followup" role="tab">
                            Follow-up
                        </a>
                    </li>
                </ul>

                <input type="hidden" name="country" value="{{ $defaultCountryId }}">
                <input type="hidden" name="add_more" value="false" id="add_more">

                <div class="tab-content p-20 pt-2">
                    <div class="tab-pane fade show active client-tab-pane" id="tab-basic" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="name" :fieldLabel="__('modules.client.clientName')" fieldName="name"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.name')"
                                              :fieldValue="old('name', $lead->client_name ?? '')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.email fieldId="email" :fieldLabel="__('app.email')" fieldName="email"
                                               :fieldPlaceholder="__('placeholders.email')"
                                               :fieldValue="old('email', $lead->client_email ?? '')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.label class="my-3" fieldId="mobile" fieldRequired="true"
                                               fieldLabel="Mobile / WhatsApp"></x-forms.label>
                                <x-forms.input-group style="margin-top:-4px">
                                    <x-forms.select fieldId="country_phonecode" fieldName="country_phonecode" search="true">
                                        @foreach ($countries as $item)
                                            <option @selected($defaultPhoneCode == $item->phonecode && !is_null($item->numcode))
                                                    data-tokens="{{ $item->name }}"
                                                    data-content="{{ $item->flagSpanCountryCode() }}"
                                                    value="{{ $item->phonecode }}">
                                            </option>
                                        @endforeach
                                    </x-forms.select>
                                    <input type="tel" class="form-control height-35 f-14" placeholder="@lang('placeholders.mobile')"
                                           name="mobile" id="mobile" value="{{ old('mobile', $lead->mobile ?? '') }}" required>
                                </x-forms.input-group>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group my-3">
                                    <x-forms.label fieldId="status" :fieldLabel="__('app.status')"></x-forms.label>
                                    <div class="d-flex">
                                        <x-forms.radio fieldId="status-active" :fieldLabel="__('app.active')" fieldValue="active"
                                                       fieldName="status" :checked="old('status', 'active') == 'active'" />
                                        <x-forms.radio fieldId="status-inactive" :fieldLabel="__('app.inactive')" fieldValue="deactive"
                                                       fieldName="status" :checked="old('status') == 'deactive'" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.file allowedFileExtensions="png jpg jpeg svg bmp"
                                              class="mr-0 mr-lg-2 mr-md-2 cropper"
                                              :fieldLabel="__('modules.profile.profilePicture')"
                                              fieldName="image"
                                              fieldId="image"
                                              fieldHeight="118"
                                              :popover="__('messages.fileFormat.ImageFile')" />
                            </div>

                            <div class="col-lg-12">
                                <x-forms.textarea fieldName="address" fieldId="address" fieldRequired="true"
                                                  :fieldLabel="__('modules.accountSettings.companyAddress')"
                                                  :fieldPlaceholder="__('placeholders.address')"
                                                  :fieldValue="old('address', $lead->address ?? '')" />
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade client-tab-pane" id="tab-company" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="company_name" :fieldLabel="__('modules.client.companyName')"
                                              fieldName="company_name"
                                              :fieldValue="old('company_name', $lead->company_name ?? '')"
                                              :fieldPlaceholder="__('placeholders.company')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="office" :fieldLabel="__('modules.client.officePhoneNumber')"
                                              fieldName="office"
                                              :fieldPlaceholder="__('placeholders.mobileWithPlus')"
                                              :fieldValue="old('office', $lead->office ?? '')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="website" :fieldLabel="__('modules.client.website')"
                                              fieldName="website"
                                              :fieldValue="old('website', $lead->website ?? '')"
                                              :fieldPlaceholder="__('placeholders.website')" />
                            </div>

                            <div class="col-lg-12">
                                <x-forms.file allowedFileExtensions="png jpg jpeg svg bmp"
                                              class="mr-0 mr-lg-2 mr-md-2"
                                              :fieldLabel="__('modules.contracts.companyLogo')"
                                              fieldName="company_logo"
                                              :fieldValue="company()->logo_url"
                                              fieldId="company_logo"
                                              :popover="__('messages.fileFormat.ImageFile')" />
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade client-tab-pane" id="tab-followup" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <x-forms.select fieldId="client_type" fieldName="client_type" fieldLabel="Client Type">
                                    <option value="">--</option>
                                    <option value="hot" @selected(old('client_type') === 'hot')>Hot</option>
                                    <option value="warm" @selected(old('client_type') === 'warm')>Warm</option>
                                    <option value="cold" @selected(old('client_type') === 'cold')>Cold</option>
                                </x-forms.select>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.datepicker fieldId="last_contact_date" fieldName="last_contact_date"
                                                    custom="true" fieldLabel="Last Contact Date"
                                                    fieldPlaceholder="Select last contact date"
                                                    :fieldValue="old('last_contact_date')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.datepicker fieldId="next_followup_date" fieldName="next_followup_date"
                                                    custom="true" fieldLabel="Next Follow-up Date"
                                                    fieldPlaceholder="Select next follow-up date"
                                                    :fieldValue="old('next_followup_date')" />
                            </div>

                            @if ($addPermission == 'all')
                                <div class="col-lg-4 col-md-6">
                                    <x-forms.select fieldId="assigned_to" fieldLabel="Assigned To" fieldName="assigned_to">
                                        <option value="">--</option>
                                        @foreach ($employees as $item)
                                            <x-user-option :user="$item" :selected="(int) old('assigned_to', user()->id) === (int) $item->id" />
                                        @endforeach
                                    </x-forms.select>
                                </div>
                            @endif

                            <div class="col-lg-12">
                                <x-forms.textarea fieldId="note" fieldLabel="Notes" fieldName="note"
                                                  :fieldPlaceholder="__('modules.lead.remark')"
                                                  :fieldValue="old('note')" />
                            </div>
                        </div>
                    </div>
                </div>

                @includeIf('einvoice::form.client-create')
                @if (isset($fields))
                    <x-forms.custom-field :fields="$fields"></x-forms.custom-field>
                @endif

                <x-form-actions>
                    <x-forms.button-primary id="save-client-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
                    <x-forms.button-secondary class="mr-3" id="save-more-client-form" icon="check-double">@lang('app.saveAddMore')</x-forms.button-secondary>
                    <x-forms.button-cancel :link="route('clients.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
                </x-form-actions>
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

        $('#save-more-client-form').click(function() {
            $('#add_more').val(true);

            const url = "{{ route('clients.store') }}?add_more=true";
            const data = $('#save-client-data-form').serialize();

            saveClient(data, url, "#save-more-client-form");
        });

        $('#save-client-form').click(function() {
            $('#add_more').val(false);

            const url = "{{ route('clients.store') }}";
            const data = $('#save-client-data-form').serialize();

            saveClient(data, url, "#save-client-form");
        });

        function saveClient(data, url, buttonSelector) {
            $.easyAjax({
                url: url,
                container: '#save-client-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: buttonSelector,
                file: true,
                data: data,
                success: function(response) {
                    if (response.status == 'success') {
                        if ($(MODAL_XL).hasClass('show')) {
                            $(MODAL_XL).modal('hide');
                            window.location.reload();
                        } else if (typeof response.redirectUrl !== 'undefined') {
                            window.location.href = response.redirectUrl;
                        } else if (response.add_more == true) {
                            var right_modal_content = $.trim($(RIGHT_MODAL_CONTENT).html());

                            if (right_modal_content.length) {
                                $(RIGHT_MODAL_CONTENT).html(response.html.html);
                            } else {
                                $('.content-wrapper').html(response.html.html);
                                init('.content-wrapper');
                            }

                            $('#add_more').val(false);
                        }

                        if (typeof showTable !== 'undefined' && typeof showTable === 'function') {
                            showTable();
                        }
                    }
                }
            });
        }

        <x-forms.custom-field-filejs/>
        init(RIGHT_MODAL);
    });
</script>
