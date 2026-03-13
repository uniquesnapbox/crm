<!-- ROW START -->
<div class="row">
    <!--  USER CARDS START -->
    <div class="col-xl-12 col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4 mb-md-0">

        <x-cards.data :title="__('modules.client.profileInfo')">
            @php
                $contactNumber = $leadContact->mobile ?: ($leadContact->cell ?: $leadContact->office);
                $sanitizedPhone = $contactNumber ? preg_replace('/\D+/', '', $contactNumber) : null;
                $whatsAppUrl = $sanitizedPhone ? 'https://wa.me/' . $sanitizedPhone : null;
                $mailUrl = $leadContact->client_email ? 'mailto:' . $leadContact->client_email : null;
                $callUrl = $contactNumber ? 'tel:' . $contactNumber : null;
                $directMessageUrl = (!is_null($leadContact->client_id) && in_array('messages', user_modules()))
                    ? route('messages.index') . '?user=' . $leadContact->client_id
                    : null;
            @endphp

            <div class="col-12 px-0 pb-3 d-flex flex-wrap">
                @if (!$leadContact->client_id)
                    <button type="button" class="btn btn-primary rounded f-14 p-2 mr-2 mb-2 convert-lead-to-client"
                        data-url="{{ route('lead-contact.convert_to_client', $leadContact->id) }}">
                        <i class="fa fa-user-check mr-1"></i>@lang('modules.lead.changeToClient')
                    </button>
                @endif

                @if ($callUrl)
                    <x-forms.link-secondary class="mr-2 mb-2" :link="$callUrl" icon="phone">Call</x-forms.link-secondary>
                @else
                    <button type="button" class="btn btn-secondary rounded f-14 p-2 mr-2 mb-2" disabled><i class="fa fa-phone mr-1"></i>Call</button>
                @endif

                @if ($whatsAppUrl)
                    <x-forms.link-secondary class="mr-2 mb-2" :link="$whatsAppUrl" icon="whatsapp" target="_blank">WhatsApp</x-forms.link-secondary>
                @else
                    <button type="button" class="btn btn-secondary rounded f-14 p-2 mr-2 mb-2" disabled><i class="fa fa-whatsapp mr-1"></i>WhatsApp</button>
                @endif

                @if ($mailUrl)
                    <x-forms.link-secondary class="mr-2 mb-2" :link="$mailUrl" icon="envelope">@lang('app.email')</x-forms.link-secondary>
                @else
                    <button type="button" class="btn btn-secondary rounded f-14 p-2 mr-2 mb-2" disabled><i class="fa fa-envelope mr-1"></i>@lang('app.email')</button>
                @endif

                @if ($directMessageUrl)
                    <x-forms.link-secondary class="mr-2 mb-2" :link="$directMessageUrl" icon="comments">@lang('app.menu.messages')</x-forms.link-secondary>
                @else
                    <button type="button" class="btn btn-secondary rounded f-14 p-2 mr-2 mb-2" disabled><i class="fa fa-comments mr-1"></i>@lang('app.menu.messages')</button>
                @endif
            </div>

            <x-slot name="action">
                <div class="dropdown">
                    <button class="btn f-14 px-0 py-0 text-dark-grey dropdown-toggle" type="button"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-ellipsis-h"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                        aria-labelledby="dropdownMenuLink" tabindex="0">
                        <a class="dropdown-item openRightModal"
                            href="{{ route('lead-contact.edit', $leadContact->id) }}">@lang('app.edit')</a>
                        @if (
                            $deleteLeadPermission == 'all'
                            || ($deleteLeadPermission == 'added' && user()->id == $leadContact->added_by)
                            || ($deleteLeadPermission == 'owned' && user()->id == $leadContact->added_by)
                            || ($deleteLeadPermission == 'both' && user()->id == $leadContact->added_by
                                    || user()->id == $leadContact->added_by))
                            <a class="dropdown-item delete-table-row" href="javascript:;" data-id="{{ $leadContact->id }}">
                                    @lang('app.delete')
                                </a>
                        @endif
                        @if ($leadContact->client_id == null || $leadContact->client_id == '')
                            <a class="dropdown-item convert-lead-to-client" href="javascript:;"
                               data-url="{{ route('lead-contact.convert_to_client', $leadContact->id) }}">
                                @lang('modules.lead.changeToClient')
                            </a>
                            <a class="dropdown-item convert-lead-to-client" href="javascript:;"
                               data-url="{{ route('lead-contact.convert_to_client', $leadContact->id) }}"
                               data-archive="1">
                                @lang('modules.lead.changeToClient') &amp; Archive
                            </a>
                        @endif
                    </div>
                </div>
            </x-slot>
            <x-cards.data-row :label="__('app.name')" :value="$leadContact->client_name ?? '--'" />

            <x-cards.data-row :label="__('app.email')" :value="$leadContact->client_email ?? '--'" />

            @if(!is_null($leadContact->added_by))
                <div class="col-12 px-0 pb-3 d-block d-lg-flex d-md-flex">
                    <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                        @lang('app.addedBy')</p>
                    <p class="mb-0 text-dark-grey f-14 ">
                        <x-employee :user="$leadContact->addedBy" />
                    </p>
                </div>
            @endif

            <x-cards.data-row :label="__('modules.lead.source')" :value="$leadContact->leadSource ? $leadContact->leadSource->type : '--'" />

            <x-cards.data-row :label="__('modules.lead.leadCategory')" :value="$leadContact->category->category_name ?? '--'" />

            <x-cards.data-row :label="__('modules.lead.companyName')" :value="!empty($leadContact->company_name) ? $leadContact->company_name : '--'" />

            <x-cards.data-row :label="__('modules.lead.website')" :value="$leadContact->website ?? '--'" />

            <x-cards.data-row :label="__('modules.lead.mobile')" :value="$leadContact->mobile ?? '--'" />

            <x-cards.data-row :label="__('modules.client.officePhoneNumber')" :value="$leadContact->office ?? '--'" />

            <x-cards.data-row :label="__('app.country')" :value="$leadContact->country ?? '--'" />

            <x-cards.data-row :label="__('modules.stripeCustomerAddress.state')" :value="$leadContact->state ?? '--'" />

            <x-cards.data-row :label="__('modules.stripeCustomerAddress.city')" :value="$leadContact->city ?? '--'" />

            <x-cards.data-row :label="__('modules.stripeCustomerAddress.postalCode')" :value="$leadContact->postal_code ?? '--'" />

            <x-cards.data-row :label="__('modules.lead.address')" :value="$leadContact->address ?? '--'" />

            {{-- Custom fields data --}}
            <x-forms.custom-field-show :fields="$fields" :model="$leadContact"></x-forms.custom-field-show>

        </x-cards.data>
    </div>
    <!--  USER CARDS END -->
</div>
<!-- ROW END -->
<script>
    $('body').off('click.convertLead').on('click.convertLead', '.convert-lead-to-client', function() {
        const url = $(this).data('url');
        const archive = $(this).data('archive') ? 1 : 0;

        Swal.fire({
            title: "@lang('modules.lead.changeToClient')",
            text: "This will create a client from the current lead.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: "@lang('modules.lead.changeToClient')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.easyAjax({
                url: url,
                type: 'POST',
                blockUI: true,
                data: {
                    _token: "{{ csrf_token() }}",
                    archive: archive
                },
                success: function(response) {
                    if (response.status === 'success' && response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });
    });
</script>
