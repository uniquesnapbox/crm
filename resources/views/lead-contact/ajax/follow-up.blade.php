@php
$addLeadFollowUpPermission = user()->permission('add_lead_follow_up');
$viewLeadFollowUpPermission = user()->permission('view_lead_follow_up');
@endphp

<div class="row">
    <div class="col-lg-12 col-md-12 mb-4 mb-xl-0 mb-lg-4">
        <div class="d-flex" id="table-actions">
            @if ($addLeadFollowUpPermission == 'all' || $addLeadFollowUpPermission == 'added')
                <x-forms.button-primary icon="plus" id="add-lead-followup" class="mr-3">
                    @lang('modules.followup.newFollowUp')
                </x-forms.button-primary>
            @endif
        </div>

        @if (in_array($viewLeadFollowUpPermission, ['all', 'added', 'both', 'owned']))
            <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
                {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
            </div>
        @endif
    </div>
</div>

@include('sections.datatable_js')
<script>
    $('#leadfollowup-table').on('preXhr.dt', function(e, settings, data) {
        data.leadId = "{{ $leadContact->id }}";
    });

    const refreshLeadFollowUpTable = () => {
        window.LaravelDataTables["leadfollowup-table"].draw(false);
    };

    $('body').on('click', '.delete-table-row-lead', function() {
        const id = $(this).data('followup-id');

        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            let url = "{{ route('lead-contact.follow_up_delete', ':id') }}";
            url = url.replace(':id', id);

            $.easyAjax({
                type: 'POST',
                url: url,
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.status === "success") {
                        refreshLeadFollowUpTable();
                    }
                }
            });
        });
    });

    $('#add-lead-followup').click(function() {
        const url = "{{ route('lead-contact.follow_up', $leadContact->id) }}";
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('click', '.edit-table-row-lead', function() {
        let url = "{{ route('lead-contact.follow_up_edit', ':id') }}";
        url = url.replace(':id', $(this).data('followup-id'));
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

    $('body').on('change', '.status', function() {
        $.easyAjax({
            url: "{{ route('lead-contact.change_follow_up_status') }}",
            type: 'POST',
            blockUI: true,
            data: {
                _token: "{{ csrf_token() }}",
                id: $(this).data('followup-id'),
                status: $(this).val()
            }
        });
    });
</script>
