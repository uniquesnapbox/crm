@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@push('styles')
    <style>
        @media (min-width: 992px) {
            .lead-contact-toolbar {
                display: flex !important;
                align-items: center;
                min-height: 46px;
                overflow: visible;
                white-space: nowrap;
                flex-wrap: nowrap !important;
                justify-content: flex-start;
            }

            .lead-contact-toolbar > * {
                min-width: 0;
            }

            .lead-contact-toolbar .select-box,
            .lead-contact-toolbar .task-search,
            .lead-contact-toolbar .more-filters,
            .lead-contact-toolbar #table-actions {
                white-space: nowrap;
            }

            .lead-contact-toolbar #table-actions {
                display: flex;
                align-items: center;
                flex-wrap: nowrap !important;
                flex: 0 0 auto;
                gap: 6px;
                min-width: 0;
                padding-left: 10px;
                padding-right: 10px !important;
                border-right: 1px solid #e8eef3;
            }

            .lead-contact-toolbar #table-actions .btn {
                margin-bottom: 0 !important;
                white-space: nowrap;
                min-height: 32px;
                height: 32px;
                padding: 0.35rem 0.65rem;
                border-radius: 5px;
                font-size: 12px;
                line-height: 1.2;
            }

            .lead-contact-toolbar .task-search {
                flex: 1 1 240px;
                width: auto;
                min-width: 180px;
                max-width: none;
            }

            .lead-contact-toolbar .select-box p,
            .lead-contact-toolbar .more-filters a {
                white-space: nowrap;
                font-size: 12px;
            }

            .lead-contact-toolbar .select-box .form-control,
            .lead-contact-toolbar .task-search .form-control,
            .lead-contact-toolbar .select-picker {
                min-height: 32px;
                height: 32px;
                font-size: 12px;
            }

            .lead-contact-toolbar .select-box {
                flex: 0 0 auto;
                padding: 0 10px !important;
            }

            .lead-contact-toolbar .select-box .input-group-text {
                padding: 0.25rem 0.4rem;
            }

            .lead-contact-toolbar .select-box .form-control {
                min-width: 95px;
            }

            .lead-contact-toolbar .more-filters {
                flex: 0 0 auto;
                padding-right: 12px;
            }
        }

        .lead-contact-actions-toggle {
            min-width: 104px;
            box-shadow: 0 2px 5px rgba(29, 130, 245, 0.18);
            font-weight: 500;
            letter-spacing: 0.01em;
        }

        .lead-contact-actions .dropdown-menu {
            min-width: 210px;
            margin-top: 6px;
            border-radius: 7px !important;
            box-shadow: 0 10px 28px rgba(40, 49, 60, 0.14);
        }

        .lead-contact-actions .dropdown-item {
            display: flex;
            align-items: center;
            min-height: 36px;
            padding: 0.45rem 0.85rem;
            font-size: 12px;
        }

        .lead-contact-actions .dropdown-item i {
            width: 20px;
            color: #616e80;
            text-align: center;
        }

        .lead-contact-actions .dropdown-item.disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .lead-contact-bulk-bar {
            align-items: center;
            min-height: 46px;
        }

        .lead-contact-bulk-bar.is-visible {
            display: flex !important;
        }

        .lead-contact-bulk-bar #quick-action-form {
            display: block;
        }

        @media (max-width: 991.98px) {
            .lead-contact-toolbar #table-actions {
                padding: 10px 12px;
            }

            .lead-contact-toolbar .select-box,
            .lead-contact-toolbar .task-search,
            .lead-contact-toolbar .more-filters {
                width: 100%;
            }

            .lead-contact-actions .dropdown-menu {
                left: 0;
                right: auto;
            }
        }

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

        #lead-contact-table .lead-inline-select-wrap .form-control {
            height: 30px;
            font-size: 12px;
            border-radius: 8px;
        }
    </style>
@endpush

@php
$addLeadPermission = user()->permission('add_lead');
$addLeadCustomFormPermission = user()->permission('manage_lead_custom_forms');
$canBulkAssignLead = $canBulkAssignLead ?? false;
@endphp

@section('filter-section')

    @include('lead-contact.filters')

@endsection

    @section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
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
        const leadContactPageStateKey = "lead-contact-table:last-page";

        function getLeadContactDataTable() {
            return window.LaravelDataTables ? window.LaravelDataTables[leadContactTableId] : null;
        }

        function getLeadContactSavedPage() {
            const urlParams = new URLSearchParams(window.location.search);
            const pageFromQuery = urlParams.get('dt_page');

            if (pageFromQuery !== null && pageFromQuery !== '' && Number.isFinite(Number(pageFromQuery))) {
                return Math.max(0, parseInt(pageFromQuery, 10));
            }

            const rawState = sessionStorage.getItem(leadContactPageStateKey);

            if (!rawState) {
                return null;
            }

            try {
                const state = JSON.parse(rawState);
                const page = Number(state.page);

                return Number.isFinite(page) && page >= 0 ? page : null;
            } catch (error) {
                return null;
            }
        }

        function storeLeadContactPageState() {
            const table = getLeadContactDataTable();

            if (!table || typeof table.page !== 'function') {
                return;
            }

            const info = table.page.info();
            sessionStorage.setItem(leadContactPageStateKey, JSON.stringify({
                page: info.page || 0,
                length: info.length || 0,
                updatedAt: Date.now()
            }));
        }

        function restoreLeadContactPageState(attempt = 0) {
            const table = getLeadContactDataTable();
            const page = getLeadContactSavedPage();

            if (!table || typeof table.page !== 'function') {
                if (attempt < 20) {
                    window.setTimeout(function() {
                        restoreLeadContactPageState(attempt + 1);
                    }, 100);
                }

                return false;
            }

            if (page === null) {
                return false;
            }

            const currentPage = table.page.info().page || 0;

            if (currentPage !== page) {
                table.page(page).draw('page');
            }

            return true;
        }

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

        $('#' + leadContactTableId).on('page.dt length.dt', function() {
            window.setTimeout(storeLeadContactPageState, 0);
        });

        $('#' + leadContactTableId).on('draw.dt', function() {
            leadContactSyncBulkActionState();
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

                if (actionValue == 'assign-to') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-agent-action').removeClass('d-none');
                    const $assignedTo = $('#assigned_to');
                    $assignedTo.prop('disabled', false);
                    if ($assignedTo.length > 0 && typeof $assignedTo.selectpicker === 'function') {
                        $assignedTo.selectpicker('enable');
                    }
                } else {
                    $('.quick-action-field').addClass('d-none');
                    const $assignedTo = $('#assigned_to');
                    $assignedTo.prop('disabled', true);
                    if ($assignedTo.length > 0 && typeof $assignedTo.selectpicker === 'function') {
                        $assignedTo.selectpicker('disable');
                    }
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
                const $assignedTo = $('#assigned_to');
                $assignedTo.prop('disabled', true);
                if ($assignedTo.length > 0 && typeof $assignedTo.selectpicker === 'function') {
                    $assignedTo.selectpicker('disable');
                }
            }
        });

        $('#quick-action-apply').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'assign-to' && ($('#assigned_to').val() || '') === '') {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "Please select an employee to assign the selected leads.",
                    icon: 'warning',
                    confirmButtonText: "@lang('app.ok')",
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
                return;
            }
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
            var rowdIds = $("#lead-contact-table input[name='datatable_ids[]']:checked").map(function() {
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

        $('body').on('click', '#lead-contact-table tbody tr.lead-table-row', function(e) {
            if ($(e.target).closest('a,button,input,select,option,label,.select-picker,.bootstrap-select,.bootstrap-select *,.js-lead-table-inline-select,.dropdown,.dropdown-menu,.swal2-container').length) {
                return;
            }

            const rowId = ($(this).attr('id') || '').replace('row-', '');
            if (!rowId) {
                return;
            }

            storeLeadContactPageState();
            window.location.href = leadShowRouteTemplate.replace(':id', rowId);
        });

        $('body').on('click', '#lead-contact-table a.js-lead-contact-open', function() {
            storeLeadContactPageState();
        });

        $('body').on('change', '.js-lead-table-inline-select', function() {
            const $field = $(this);
            const url = $field.data('url');
            const field = ($field.data('field') || '').toString();
            const value = ($field.val() || '').toString();
            const previousValue = ($field.attr('data-prev-value') || '').toString();

            if (!url || !field || value === previousValue) {
                return;
            }

            $field.prop('disabled', true);

            $.easyAjax({
                url: url,
                type: 'POST',
                blockUI: false,
                data: {
                    _token: "{{ csrf_token() }}",
                    field: field,
                    value: value
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $field.attr('data-prev-value', value);
                        window.setTimeout(showTable, 0);
                    } else {
                        $field.val(previousValue);
                    }
                },
                error: function() {
                    $field.val(previousValue);
                },
                complete: function() {
                    $field.prop('disabled', false);
                    if (typeof $.easyUnblockUI === 'function') {
                        $.easyUnblockUI();
                    } else if (typeof $.unblockUI === 'function') {
                        $.unblockUI();
                    }
                }
            });
        });

        $( document ).ready(function() {
            leadContactHideBulkActions();

            const savedPage = getLeadContactSavedPage();
            if (savedPage !== null) {
                storeLeadContactPageState();
            }

            window.setTimeout(function() {
                restoreLeadContactPageState();
            }, 0);

            @if (!is_null(request('start')) && !is_null(request('end')))
            $('#datatableRange').val('{{ request('start') }}' +
            ' @lang("app.to") ' + '{{ request('end') }}');
            $('#datatableRange').data('daterangepicker').setStartDate("{{ request('start') }}");
            $('#datatableRange').data('daterangepicker').setEndDate("{{ request('end') }}");
                showTable();
            @endif
        });

        const leadContactScheduleBulkActionUpdate = (callback) => {
            window.setTimeout(callback, 0);
        };

        let leadContactBulkActionsVisible = false;

        const leadContactSetAssignedToState = (enabled) => {
            const $assignedTo = $('#assigned_to');

            if ($assignedTo.length === 0) {
                return;
            }

            $assignedTo.prop('disabled', !enabled);

            if (typeof $assignedTo.selectpicker === 'function') {
                $assignedTo.selectpicker(enabled ? 'enable' : 'disable');
            }
        };

        const leadContactShowBulkActions = () => {
            const form = document.getElementById('quick-action-form');
            const bulkBar = document.getElementById('lead-contact-bulk-bar');

            if (bulkBar) {
                bulkBar.classList.add('is-visible');
                bulkBar.classList.remove('d-none');
            }

            if (form) {
                form.classList.remove('d-none');
                form.style.display = '';
            }

            if (leadContactBulkActionsVisible) {
                return;
            }

            leadContactBulkActionsVisible = true;

            const $actionType = $('#quick-action-type');
            $actionType.prop('disabled', false);

            if ($actionType.val() == '') {
                $('#quick-action-apply').prop('disabled', true);
            }

            // Keep the assignee picker disabled until the user explicitly chooses assign-to.
            leadContactSetAssignedToState(false);
        };

        const leadContactHideBulkActions = () => {
            const form = document.getElementById('quick-action-form');
            const bulkBar = document.getElementById('lead-contact-bulk-bar');

            if (bulkBar) {
                bulkBar.classList.remove('is-visible');
                bulkBar.classList.add('d-none');
            }

            if (form) {
                form.classList.add('d-none');
                form.style.display = 'none';
            }

            if (!leadContactBulkActionsVisible) {
                return;
            }

            leadContactBulkActionsVisible = false;

            const $fields = $('#quick-actions').find('input, textarea, button, select');
            $fields.prop('disabled', true);
            leadContactSetAssignedToState(false);
        };

        const leadContactSyncBulkActionState = () => {
            const $selectedRows = $(".select-table-row:checked");
            const selectedCount = $selectedRows.length;
            const $selectAll = $("#select-all-table");

            $('#lead-contact-selected-count').text(selectedCount + ' selected');

            if (selectedCount > 0) {
                leadContactShowBulkActions();

                if ($selectAll.length > 0) {
                    const selectableCount = $(".select-table-row:not(:disabled)").length;
                    $selectAll.prop("indeterminate", selectedCount > 0 && selectedCount < selectableCount);
                    $selectAll.prop("checked", selectedCount === selectableCount);
                }

                const actionValue = $("#quick-action-type").val();
                if (actionValue == "assign-to") {
                    $('#change-agent-action').removeClass('d-none');
                    leadContactSetAssignedToState(true);
                } else {
                    $('#change-agent-action').addClass('d-none');
                    leadContactSetAssignedToState(false);
                }

                if (actionValue == "") {
                    $("#quick-action-apply").attr("disabled", true);
                }

            } else {
                leadContactHideBulkActions();

                if ($selectAll.length > 0) {
                    $selectAll.prop("indeterminate", false);
                    $selectAll.prop("checked", false);
                }

                window.resetActionButtons();
            }
        };

        window.dataTableRowCheck = (id) => {
            const checkbox = document.getElementById("datatable-row-" + id);
            const row = document.getElementById("row-" + id);

            if (checkbox && row) {
                row.classList.toggle("table-active", checkbox.checked);
            }

            if (checkbox && checkbox.checked) {
                leadContactShowBulkActions();
            }

            leadContactScheduleBulkActionUpdate(leadContactSyncBulkActionState);
        };

        window.selectAllTable = (source) => {
            const shouldCheck = !!source.checked;
            const checkboxes = document.getElementsByName("datatable_ids[]");

            for (let i = 0, n = checkboxes.length; i < n; i++) {
                if (checkboxes[i].disabled) {
                    continue;
                }

                checkboxes[i].checked = shouldCheck;

                const row = checkboxes[i].closest("tr");
                if (row) {
                    row.classList.toggle("table-active", shouldCheck);
                }
            }

            if (shouldCheck) {
                leadContactShowBulkActions();
            } else {
                leadContactHideBulkActions();
            }

        };

        window.resetActionButtons = () => {
            const form = document.getElementById('quick-action-form');

            if (form && typeof form.reset === 'function') {
                form.reset();
            }

            leadContactHideBulkActions();
        };

    </script>
@endpush
