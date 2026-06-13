@php
    $indiaCountry = collect($countries)->first(function ($country) {
        return strtoupper($country->iso ?? '') === 'IN';
    });

    $clientDetails = $client->clientDetails;
    $defaultCountryId = old('country', $client->country_id ?: optional($indiaCountry)->id);
    $defaultPhoneCode = old('country_phonecode', $client->country_phonecode ?: optional($indiaCountry)->phonecode ?: 91);
    $lastContactDate = optional($clientDetails)->last_contact_date;
    $nextFollowupDate = optional($clientDetails)->next_followup_date;
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
        <x-form id="save-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('app.update') @lang('app.client')
                </h4>

                <ul class="nav nav-pills p-20 pb-0 client-edit-tabs" id="client-edit-tabs" role="tablist">
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

                <div class="tab-content p-20 pt-2">
                    <div class="tab-pane fade show active client-tab-pane" id="tab-basic" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="name" :fieldLabel="__('modules.client.clientName')" fieldName="name"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.name')" :fieldValue="$client->name" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.email fieldId="email" :fieldLabel="__('app.email')" fieldName="email"
                                               :fieldPlaceholder="__('placeholders.email')" :fieldValue="$client->email" />
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
                                           name="mobile" id="mobile" value="{{ $client->mobile }}" required>
                                </x-forms.input-group>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group my-3">
                                    <x-forms.label fieldId="status" :fieldLabel="__('app.status')"></x-forms.label>
                                    <div class="d-flex">
                                        <x-forms.radio fieldId="status-active" :fieldLabel="__('app.active')" fieldValue="active"
                                                       fieldName="status" :checked="($client->status == 'active')" />
                                        <x-forms.radio fieldId="status-inactive" :fieldLabel="__('app.inactive')" fieldValue="deactive"
                                                       fieldName="status" :checked="($client->status == 'deactive')" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.file allowedFileExtensions="png jpg jpeg svg bmp"
                                              class="mr-0 mr-lg-2 mr-md-2 cropper"
                                              :fieldLabel="__('modules.profile.profilePicture')"
                                              :fieldValue="$client->image_url"
                                              fieldName="image"
                                              fieldId="image"
                                              fieldHeight="118"
                                              :popover="__('messages.fileFormat.ImageFile')" />
                            </div>

                            <div class="col-lg-12">
                                <x-forms.textarea fieldName="address" fieldId="address" fieldRequired="true"
                                                  :fieldLabel="__('modules.accountSettings.companyAddress')"
                                                  :fieldPlaceholder="__('placeholders.address')"
                                                  :fieldValue="optional($clientDetails)->address" />
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade client-tab-pane" id="tab-company" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="company_name" :fieldLabel="__('modules.client.companyName')"
                                              fieldName="company_name"
                                              :fieldValue="optional($clientDetails)->company_name"
                                              :fieldPlaceholder="__('placeholders.company')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="office" :fieldLabel="__('modules.client.officePhoneNumber')"
                                              fieldName="office"
                                              :fieldPlaceholder="__('placeholders.mobileWithPlus')"
                                              :fieldValue="optional($clientDetails)->office" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="website" :fieldLabel="__('modules.client.website')"
                                              fieldName="website"
                                              :fieldValue="optional($clientDetails)->website"
                                              :fieldPlaceholder="__('placeholders.website')" />
                            </div>

                            <div class="col-lg-12">
                                <x-forms.file allowedFileExtensions="png jpg jpeg svg bmp"
                                              class="mr-0 mr-lg-2 mr-md-2"
                                              :fieldLabel="__('modules.contracts.companyLogo')"
                                              fieldName="company_logo"
                                              :fieldValue="(optional($clientDetails)->company_logo ? optional($clientDetails)->image_url : null)"
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
                                    <option value="hot" @selected(optional($clientDetails)->client_type === 'hot')>Hot</option>
                                    <option value="warm" @selected(optional($clientDetails)->client_type === 'warm')>Warm</option>
                                    <option value="cold" @selected(optional($clientDetails)->client_type === 'cold')>Cold</option>
                                </x-forms.select>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.datepicker fieldId="last_contact_date" fieldName="last_contact_date"
                                                    custom="true" fieldLabel="Last Contact Date"
                                                    fieldPlaceholder="Select last contact date"
                                                    :fieldValue="($lastContactDate ? optional($lastContactDate)->format(company()->date_format) : '')" />
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.datepicker fieldId="next_followup_date" fieldName="next_followup_date"
                                                    custom="true" fieldLabel="Next Follow-up Date"
                                                    fieldPlaceholder="Select next follow-up date"
                                                    :fieldValue="($nextFollowupDate ? optional($nextFollowupDate)->format(company()->date_format) : '')" />
                            </div>

                            @if ($editPermission == 'all')
                                <div class="col-lg-4 col-md-6">
                                    <x-forms.select fieldId="assigned_to" fieldLabel="Assigned To" fieldName="assigned_to">
                                        <option value="">--</option>
                                        @foreach ($employees as $item)
                                            <x-user-option :user="$item" :selected="optional($clientDetails)->added_by == $item->id" />
                                        @endforeach
                                    </x-forms.select>
                                </div>
                            @endif

                            <div class="col-lg-12">
                                <x-forms.textarea fieldId="note" fieldLabel="Notes" fieldName="note"
                                                  :fieldPlaceholder="__('modules.lead.remark')"
                                                  :fieldValue="optional($clientDetails)->note" />
                            </div>
                        </div>
                    </div>
                </div>

                @includeIf('einvoice::form.client-edit')
                @if (isset($fields) && isset($clientDetail))
                    <x-forms.custom-field :fields="$fields" :model="$clientDetail"></x-forms.custom-field>
                @endif

                <x-form-actions>
                    <x-forms.button-primary id="save-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
                    <x-forms.button-cancel :link="route('clients.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

@if (function_exists('sms_setting') && sms_setting()->telegram_status)
    <script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>
@endif

<script>
    $(document).ready(function() {
        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        $('#save-form').click(function() {
            const url = "{{ route('clients.update', $client->id) }}";

            $.easyAjax({
                url: url,
                container: '#save-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                file: true,
                buttonSelector: "#save-form",
                data: $('#save-data-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });

        <x-forms.custom-field-filejs/>
        init(RIGHT_MODAL);
    });
</script>
