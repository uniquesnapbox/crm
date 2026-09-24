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

        .lead-contact-toolbar #table-actions .btn,
        .lead-contact-toolbar > .select-box .bootstrap-select > .dropdown-toggle,
        .lead-contact-toolbar > .ml-auto .more-filters > .js-open-more-filter {
            background-color: #082b78 !important;
            border-color: #082b78 !important;
            color: #fff !important;
            border: 1px solid #082b78 !important;
            border-radius: 5px !important;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .lead-contact-toolbar #table-actions .btn:hover,
        .lead-contact-toolbar #table-actions .btn:focus,
        .lead-contact-toolbar > .select-box .bootstrap-select > .dropdown-toggle:hover,
        .lead-contact-toolbar > .select-box .bootstrap-select > .dropdown-toggle:focus,
        .lead-contact-toolbar > .ml-auto .more-filters > .js-open-more-filter:hover,
        .lead-contact-toolbar > .ml-auto .more-filters > .js-open-more-filter:focus {
            background-color: #f4511e !important;
            border-color: #f4511e !important;
            color: #fff !important;
        }

        .lead-contact-toolbar > .ml-auto .more-filters > .js-open-more-filter {
            min-height: 32px;
            padding: 0 11px;
            line-height: 30px;
        }

        .lead-contact-toolbar > .ml-auto .more-filters > .js-open-more-filter i {
            color: #fff !important;
        }

        .lead-contact-toolbar #datatableRange {
            background-color: #082b78 !important;
            border: 1px solid #082b78 !important;
            border-radius: 5px;
            color: #fff !important;
        }

        .lead-contact-toolbar .task-search .input-group {
            background-color: #082b78 !important;
            border: 1px solid #082b78;
            border-radius: 5px !important;
        }

        .lead-contact-toolbar .task-search .input-group-text,
        .lead-contact-toolbar .task-search .form-control {
            background-color: transparent !important;
            border-color: transparent !important;
            color: #fff !important;
        }

        .lead-contact-toolbar #datatableRange::placeholder,
        .lead-contact-toolbar .task-search .form-control::placeholder {
            color: rgba(255, 255, 255, 0.88) !important;
            opacity: 1;
        }

        .lead-contact-toolbar .task-search .input-group i {
            color: #fff !important;
        }

        .lead-contact-toolbar #datatableRange:hover,
        .lead-contact-toolbar .task-search .input-group:focus-within,
        .lead-contact-toolbar .task-search .input-group:hover,
        .lead-contact-toolbar #datatableRange:focus {
            background-color: #f4511e !important;
            border-color: #f4511e !important;
            box-shadow: 0 0 0 2px rgba(244, 81, 30, 0.12);
        }

        .lead-contact-toolbar .task-search .input-group:hover,
        .lead-contact-toolbar .task-search .input-group:focus-within {
            background-color: #f4511e !important;
        }

        .lead-contact-toolbar .more-filter-tab .bootstrap-select > .dropdown-toggle,
        .lead-contact-toolbar .more-filter-tab .btn-secondary {
            background-color: #082b78 !important;
            border-color: #082b78 !important;
            color: #fff !important;
        }

        .lead-contact-toolbar .more-filter-tab .bootstrap-select > .dropdown-toggle:hover,
        .lead-contact-toolbar .more-filter-tab .bootstrap-select > .dropdown-toggle:focus,
        .lead-contact-toolbar .more-filter-tab .bootstrap-select.show > .dropdown-toggle,
        .lead-contact-toolbar .more-filter-tab .btn-secondary:hover,
        .lead-contact-toolbar .more-filter-tab .btn-secondary:focus {
            background-color: #f4511e !important;
            border-color: #f4511e !important;
            color: #fff !important;
        }

        .lead-contact-toolbar .more-filter-tab h3,
        .lead-contact-toolbar .more-filter-tab label,
        .lead-contact-toolbar .more-filter-tab .close {
            color: #082b78 !important;
        }

        .lead-contact-toolbar .more-filter-tab .close:hover,
        .lead-contact-toolbar .more-filter-tab .close:focus {
            color: #f4511e !important;
        }

        .lead-contact-toolbar .more-filter-tab .dropdown-item.active,
        .lead-contact-toolbar .more-filter-tab .dropdown-item:active {
            background-color: #f4511e !important;
            color: #fff !important;
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
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            min-height: 46px;
        }

        .lead-contact-bulk-bar.d-none {
            display: none !important;
        }

        .lead-contact-bulk-bar.is-visible {
            display: flex !important;
        }

        .lead-contact-bulk-bar #quick-action-form {
            display: block !important;
            flex: 1 1 auto;
        }

        .lead-contact-bulk-bar #quick-actions {
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 991.98px) {
            .lead-contact-toolbar {
                display: flex !important;
                align-items: center;
                flex-wrap: nowrap !important;
                gap: 8px;
                overflow-x: auto;
                overflow-y: visible;
                white-space: nowrap;
                padding: 8px 10px !important;
                background: #fff;
            }

            .lead-contact-toolbar > * {
                flex: 0 0 auto !important;
                width: auto !important;
            }

            .lead-contact-toolbar .select-box,
            .lead-contact-toolbar .more-filters {
                width: auto !important;
            }

            .lead-contact-toolbar .lead-contact-date-filter {
                padding: 0 !important;
                border: 0 !important;
            }

            .lead-contact-toolbar .lead-contact-date-filter .select-status,
            .lead-contact-toolbar .lead-contact-date-filter #datatableRange {
                width: 198px;
            }

            .lead-contact-toolbar .lead-contact-date-filter #datatableRange {
                height: 38px;
                min-height: 38px;
                padding: 0 12px !important;
                border: 1px solid rgba(8, 43, 120, 0.25) !important;
                border-radius: 7px !important;
                background: #082b78 !important;
                color: #fff !important;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: 0.01em;
                box-shadow: 0 3px 8px rgba(8, 43, 120, 0.16);
            }

            .lead-contact-toolbar .lead-contact-date-filter #datatableRange:focus,
            .lead-contact-toolbar .lead-contact-date-filter #datatableRange:hover {
                border-color: #f4511e !important;
                background: #0b347f !important;
                box-shadow: 0 0 0 3px rgba(244, 81, 30, 0.12), 0 3px 8px rgba(8, 43, 120, 0.16);
            }

            .lead-contact-mobile-title {
                display: none !important;
            }

            .lead-contact-toolbar .lead-contact-filter-label {
                display: none !important;
            }

            .lead-contact-toolbar .lead-contact-type-filter {
                display: none !important;
            }

            .lead-contact-toolbar #search-text-field::placeholder {
                color: transparent !important;
            }

            .lead-contact-toolbar .more-filters .js-open-more-filter {
                font-size: 0 !important;
            }

            .lead-contact-toolbar .more-filters .js-open-more-filter .filter_icon {
                margin-right: 0 !important;
                font-size: 14px !important;
            }

            .lead-contact-toolbar .task-search {
                flex: 0 0 42px !important;
                width: 42px !important;
                padding: 4px 0 !important;
            }

            .lead-contact-toolbar .task-search > div,
            .lead-contact-toolbar .task-search .input-group {
                width: 42px !important;
                min-width: 42px !important;
                margin: 0 !important;
                height: 38px;
                border-radius: 7px !important;
                box-shadow: 0 3px 8px rgba(8, 43, 120, 0.16);
            }

            .lead-contact-toolbar .task-search .input-group {
                overflow: hidden;
            }

            .lead-contact-toolbar .task-search .form-control {
                display: none !important;
            }

            .lead-contact-toolbar .task-search.is-expanded {
                flex-basis: 210px !important;
                width: 210px !important;
            }

            .lead-contact-toolbar .task-search.is-expanded > div,
            .lead-contact-toolbar .task-search.is-expanded .input-group {
                width: 210px !important;
            }

            .lead-contact-toolbar .task-search.is-expanded .form-control {
                display: block !important;
                width: 168px !important;
                opacity: 1 !important;
            }

            .lead-contact-toolbar .task-search .input-group-prepend {
                margin: 0 !important;
            }

            .lead-contact-toolbar .task-search .input-group-text {
                width: 42px;
                height: 38px;
                justify-content: center;
                padding: 0 !important;
                border-radius: 7px !important;
            }

            .lead-contact-toolbar > .ml-auto {
                flex: 0 0 auto;
                width: auto !important;
            }

            .lead-contact-toolbar .more-filters {
                padding: 0 !important;
            }

            .lead-contact-toolbar .more-filters .js-open-more-filter {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 38px;
                padding: 0 !important;
                border-radius: 7px !important;
                box-shadow: 0 3px 8px rgba(8, 43, 120, 0.16);
            }

            .lead-contact-toolbar .more-filters .js-open-more-filter.d-none {
                display: none !important;
            }

            .lead-contact-toolbar .more-filters .js-open-more-filter.d-block {
                display: flex !important;
            }

            .lead-contact-toolbar .more-filter-tab h3 {
                font-size: 12px !important;
                font-weight: 700 !important;
                font-family: Arial, sans-serif !important;
            }

            .lead-contact-toolbar .more-filter-tab label {
                font-size: 10px !important;
                display: block !important;
                margin-bottom: 4px !important;
                line-height: 12px !important;
                font-family: Arial, sans-serif !important;
                font-weight: 400 !important;
            }

            .lead-contact-toolbar .more-filter-tab .more-filter-items {
                margin-bottom: 10px !important;
            }

            .lead-contact-toolbar .more-filter-tab .select-filter,
            .lead-contact-toolbar .more-filter-tab .select-others,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select > .dropdown-toggle,
            .lead-contact-toolbar .more-filter-tab .form-control {
                width: 150px !important;
                min-width: 150px !important;
                max-width: 150px !important;
                height: 25px !important;
                min-height: 25px !important;
                flex: 0 0 150px !important;
            }

            .lead-contact-toolbar .more-filter-tab .bootstrap-select > .dropdown-toggle,
            .lead-contact-toolbar .more-filter-tab .form-control {
                padding: 2px 8px !important;
                font-size: 10px !important;
                line-height: 19px !important;
                font-family: Arial, sans-serif !important;
                font-weight: 600 !important;
            }

            .lead-contact-toolbar .more-filter-tab .filter-option-inner-inner,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select .dropdown-menu,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select .dropdown-item {
                font-family: Arial, sans-serif !important;
                font-size: 10px !important;
                line-height: 19px !important;
            }

            .lead-contact-toolbar .more-filter-tab .bootstrap-select > .dropdown-toggle,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select .filter-option,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select .filter-option-inner,
            .lead-contact-toolbar .more-filter-tab .bootstrap-select .filter-option-inner-inner {
                color: #fff !important;
                font-family: Arial, sans-serif !important;
                font-size: 10px !important;
                font-weight: 600 !important;
                line-height: 19px !important;
                text-align: left !important;
                text-transform: none !important;
            }

            /* Bootstrap-select menus are appended to body, so keep their
               mobile option typography consistent with the filter controls. */
            .bootstrap-select .dropdown-menu,
            .bootstrap-select .dropdown-menu .dropdown-item,
            .bootstrap-select .dropdown-menu .dropdown-item span,
            .bootstrap-select .dropdown-menu .no-results,
            .bootstrap-select .bs-searchbox input {
                font-family: Arial, sans-serif !important;
                font-size: 10px !important;
                line-height: 19px !important;
            }

            .lead-contact-toolbar .more-filter-tab .select-filter.mb-4 {
                margin-bottom: 0 !important;
            }

            .lead-contact-toolbar .more-filter-tab .more-filter-items {
                margin: 0 0 10px !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
            }

            .lead-contact-toolbar .more-filter-tab .more-filter-items > label {
                flex: 0 0 78px !important;
                width: 78px !important;
                padding: 0 !important;
                margin: 0 !important;
                white-space: nowrap !important;
            }

            .lead-contact-toolbar #table-actions {
                display: inline-flex !important;
                align-items: center;
                width: fit-content;
                padding: 0 !important;
                overflow: hidden;
                background-color: #082b78;
                border-radius: 5px;
            }

            .lead-contact-toolbar #table-actions .lead-contact-actions-toggle,
            .lead-contact-toolbar #table-actions .lead-contact-actions > button {
                min-height: 38px;
                height: 38px;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none;
            }

            .lead-contact-toolbar #table-actions .lead-contact-actions-toggle {
                padding-left: 12px;
                padding-right: 12px;
            }

            .lead-contact-toolbar #table-actions .lead-contact-actions {
                margin-left: 0 !important;
            }

            .lead-contact-toolbar #table-actions .lead-contact-actions > button {
                padding-left: 11px;
                padding-right: 11px;
                background-color: #082b78 !important;
                color: #fff !important;
            }

            .lead-contact-toolbar #table-actions {
                padding: 0 !important;
            }

            .lead-contact-toolbar #table-actions .buttons-excel {
                display: none !important;
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
            padding-top: 3px !important;
            padding-bottom: 3px !important;
            line-height: 1.2;
            vertical-align: middle;
        }

        #lead-contact-table tbody tr.lead-table-row {
            cursor: pointer;
            background: #fff;
        }

        #lead-contact-table tbody tr.lead-table-row:hover td {
            background: color-mix(in srgb, var(--lead-status-color, #8f9bb3) 45%, #fff) !important;
        }

        #lead-contact-table .lead-table-actions .btn {
            min-width: 30px;
            padding: 0.2rem 0.45rem;
        }

        #lead-contact-table .lead-table-actions {
            min-width: 104px;
            white-space: nowrap;
        }

        #lead-contact-table .lead-inline-select-wrap .form-control {
            height: 30px;
            font-size: 12px;
            border-radius: 8px;
        }

        #lead-contact-table .lead-assignee-list {
            display: none;
        }

        .content-wrapper.lead-contact-page {
            padding: 0 !important;
        }

        .lead-contact-page .w-tables {
            margin-top: 0 !important;
            border-radius: 0 !important;
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
    <div class="content-wrapper lead-contact-page">
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables bg-white table-responsive">

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
        const leadWasCreated = new URLSearchParams(window.location.search).get('lead_created') === '1';

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

        function resetLeadContactStateAfterCreate(attempt = 0) {
            if (!leadWasCreated) {
                return;
            }

            const table = getLeadContactDataTable();
            if (!table || typeof table.search !== 'function') {
                if (attempt < 20) {
                    window.setTimeout(function() {
                        resetLeadContactStateAfterCreate(attempt + 1);
                    }, 100);
                }

                return;
            }

            sessionStorage.removeItem(leadContactPageStateKey);
            if (table.state && typeof table.state.clear === 'function') {
                table.state.clear();
            }

            table.search('').page(0).draw(false);

            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('lead_created');
            cleanUrl.searchParams.delete('dt_page');
            window.history.replaceState({}, document.title, cleanUrl.toString());
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
                filter_country: $('#filter_country').val(),
                filter_state: $('#filter_state').val(),
                filter_district: $('#filter_district').val(),
                date_filter_on: $('#date_filter_on').val(),
                duplicate_leads: $('#filter_duplicate_leads').val(),
                filter_addedBy: $('#filter_addedBy').val(),
                filter_assignedTo: $('#filter_assigned_to').val()
            };
        }

        let lastLeadContactFilterState = null;

        $('#' + leadContactTableId).on('preXhr.dt', function(e, settings, data) {
            const filters = getLeadContactFilters();
            const filterState = JSON.stringify(filters);

            if (lastLeadContactFilterState !== null && lastLeadContactFilterState !== filterState) {
                settings._iDisplayStart = 0;
                data.start = 0;
            }

            lastLeadContactFilterState = filterState;
            Object.assign(data, filters);
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
            const selectedAssignees = $('#assigned_to').val() || [];
            if (actionValue == 'assign-to' && selectedAssignees.length === 0) {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "Please select at least one employee to assign the selected leads.",
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

            window.setTimeout(function() {
                resetLeadContactStateAfterCreate();
            }, 0);

            @if (!is_null(request('start')) && !is_null(request('end')))
            $('#datatableRange').val('{{ request('start') }}' +
            ' @lang("app.to") ' + '{{ request('end') }}');
            $('#datatableRange').data('daterangepicker').setStartDate("{{ request('start') }}");
            $('#datatableRange').data('daterangepicker').setEndDate("{{ request('end') }}");
                showTable();
            @endif
        });

        let leadContactBulkActionSyncPending = false;

        const leadContactScheduleBulkActionUpdate = (callback) => {
            if (leadContactBulkActionSyncPending) {
                return;
            }

            leadContactBulkActionSyncPending = true;
            window.requestAnimationFrame(function() {
                leadContactBulkActionSyncPending = false;
                callback();
            });
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

            if ($actionType.length > 0 && typeof $actionType.selectpicker === 'function') {
                $actionType.selectpicker('enable');
            }

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
            const $actionType = $('#quick-action-type');
            if ($actionType.length > 0 && typeof $actionType.selectpicker === 'function') {
                $actionType.selectpicker('disable');
            }
            leadContactSetAssignedToState(false);
        };

        const leadContactSyncBulkActionState = () => {
            const $selectedRows = $("#lead-contact-table .select-table-row:checked");
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
            const checkboxes = document.querySelectorAll("#lead-contact-table input[name='datatable_ids[]']");

            checkboxes.forEach((checkbox) => {
                if (checkbox.disabled) {
                    return;
                }

                checkbox.checked = shouldCheck;

                const row = checkbox.closest("tr");
                if (row) {
                    row.classList.toggle("table-active", shouldCheck);
                }
            });

            source.indeterminate = false;
            leadContactSyncBulkActionState();

        };

        // Keep the bulk toolbar working even when DataTables redraws the rows.
        $('#lead-contact-table')
            .off('change.leadContactBulk', '.select-table-row')
            .on('change.leadContactBulk', '.select-table-row', function() {
                const row = this.closest('tr');
                if (row) {
                    row.classList.toggle('table-active', this.checked);
                }
                leadContactSyncBulkActionState();
            });

        window.resetActionButtons = () => {
            const form = document.getElementById('quick-action-form');

            if (form && typeof form.reset === 'function') {
                form.reset();
            }

            leadContactHideBulkActions();
        };

        // On mobile, keep the search control compact and expand it when the icon is tapped.
        $(document).on('click.leadContactMobileSearch', '.lead-contact-toolbar .task-search .input-group-text', function() {
            const $search = $(this).closest('.task-search');
            $search.addClass('is-expanded');
            window.setTimeout(function() {
                $('#search-text-field').trigger('focus');
            }, 0);
        });

        $(document).on('click.leadContactMobileSearch', function(event) {
            const $search = $('.lead-contact-toolbar .task-search');

            if (!$search.is(event.target) && $search.has(event.target).length === 0 && !$('#search-text-field').val()) {
                $search.removeClass('is-expanded');
            }
        });

    </script>
@endpush
