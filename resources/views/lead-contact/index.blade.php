@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
    <style>
        #lead-contact-table tbody tr.lead-table-row td {
            padding-top: 8px !important;
            padding-bottom: 8px !important;
            line-height: 1.2;
            vertical-align: middle;
        }

        #lead-contact-table tbody tr.lead-table-row {
            cursor: pointer;
        }

        #lead-contact-table tbody tr.lead-table-row:hover {
            background: #f7fbff;
        }

        #lead-contact-table .lead-table-actions .btn {
            min-width: 30px;
            padding: 0.2rem 0.45rem;
        }
    </style>
@endpush

@section('filter-section')

    @include('lead-contact.filters')

@endsection

@php
$addLeadPermission = user()->permission('add_lead');
$addLeadCustomFormPermission = user()->permission('manage_lead_custom_forms');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-grid d-lg-flex d-md-flex action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addLeadPermission == 'all' || $addLeadPermission == 'added')
                    <x-forms.link-primary :link="route('lead-contact.create')" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0 openRightModal" icon="plus">
                        @lang('modules.leadContact.addLeadContact')
                    </x-forms.link-primary>
                @endif

                @if ($addLeadCustomFormPermission == 'all')
                    <x-forms.button-secondary icon="pencil-alt" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0" id="add-lead">
                        @lang('modules.lead.leadForm')
                    </x-forms.button-secondary>
                @endif

                @if ($addLeadPermission == 'all' || $addLeadPermission == 'added')
                    <x-forms.link-secondary :link="route('lead-contact.import')" class="mr-3 openRightModal float-left mb-2 mb-lg-0 mb-md-0 d-none d-lg-block" icon="file-upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
            </div>

            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
            </x-datatable.actions>

        </div>

        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        const leadShowRouteTemplate = "{{ route('lead-contact.show', ':id') }}";
        const leadContactTableId = "lead-contact-table";

        function getLeadContactFilters() {
            var dateRangePicker = $('#datatableRange').data('daterangepicker');
            var startDate = $('#datatableRange').val();
            var endDate = null;

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else if (dateRangePicker) {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            return {
                startDate: startDate,
                endDate: endDate,
                searchText: $('#search-text-field').val(),
                min: $('#min').val(),
                max: $('#max').val(),
                type: $('#type').val(),
                category_id: $('#filter_category_id').val(),
                source_id: $('#filter_source_id').val(),
                status_id: $('#filter_status_id').val(),
                interest_level: $('#filter_interest_level').val(),
                date_filter_on: $('#date_filter_on').val(),
                filter_addedBy: $('#filter_addedBy').val(),
                filter_assignedTo: $('#filter_assigned_to').val()
            };
        }

        $('#' + leadContactTableId).on('preXhr.dt', function(e, settings, data) {
            Object.assign(data, getLeadContactFilters());
        });

        const showTable = () => {
            window.LaravelDataTables["lead-contact-table"].draw(false);
        }

        $('body').on('click', '#table-actions .buttons-excel', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            const dt = window.LaravelDataTables[leadContactTableId];
            const url = dt.ajax.url() || window.location.href;
            const currentParams = dt.ajax.params() || {};
            const filterParams = getLeadContactFilters();

            const exportParams = Object.assign({}, currentParams, filterParams, {
                action: 'excel'
            });

            const separator = url.indexOf('?') > -1 ? '&' : '?';
            window.location = url + separator + $.param(exportParams);
        });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();

            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#reset-filters-2').click(function() {
            $('#filter-form')[0].reset();

            $('.filter-box #leave_type').val('all');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-agent') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-agent-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
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
                    if (result.isConfirmed) {
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('id');
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
                if (result.isConfirmed) {
                    var url = "{{ route('lead-contact.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        const applyQuickAction = () => {
            var rowdIds = $("#lead-contact-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            var url = "{{ route('lead-contact.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                    }
                }
            })
        };

        $('body').on('click', '#add-lead', function() {
            window.location.href = "{{ route('lead-form.index') }}";
        });

        $('body').on('click', '#lead-contact-table tbody tr.lead-table-row', function(e) {
            if ($(e.target).closest('a,button,input,label,.select-picker,.dropdown,.dropdown-menu,.swal2-container').length) {
                return;
            }

            const rowId = ($(this).attr('id') || '').replace('row-', '');
            if (!rowId) {
                return;
            }

            window.location.href = leadShowRouteTemplate.replace(':id', rowId);
        });

        $( document ).ready(function() {
            @if (!is_null(request('start')) && !is_null(request('end')))
            $('#datatableRange').val('{{ request('start') }}' +
            ' @lang("app.to") ' + '{{ request('end') }}');
            $('#datatableRange').data('daterangepicker').setStartDate("{{ request('start') }}");
            $('#datatableRange').data('daterangepicker').setEndDate("{{ request('end') }}");
                showTable();
            @endif
        });

    </script>
@endpush
