@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@push('styles')
    <style>
        .employee-lead-updates-dialog {
            width: 90vw;
            max-width: 1400px;
        }

        .employee-lead-updates-dialog .modal-content {
            aspect-ratio: 16 / 9;
            max-height: calc(100vh - 2rem);
        }

        .employee-lead-updates-dialog .modal-body {
            overflow-y: auto;
        }

        .employee-lead-activity-table-wrap {
            max-height: 100%;
        }

        .employee-lead-activity-table-wrap table {
            min-width: 820px;
        }

        .employee-lead-activity-summary {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: .8rem;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            background: #f8f9fa;
            padding: .55rem .7rem;
            font-size: 12px;
            white-space: nowrap;
        }

        .employee-lead-activity-summary-group {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: .35rem;
        }

        .employee-lead-activity-summary-group + .employee-lead-activity-summary-group {
            margin-top: 0;
        }

        .employee-lead-activity-summary-item {
            display: inline-block;
            border-radius: 10px;
            padding: .12rem .45rem;
            color: #374151;
            background: #eef2f7;
        }

        .employee-lead-activity-summary-item.is-success {
            color: #ffffff;
            background: #198754;
        }

        .employee-lead-activity-summary-item.is-danger {
            color: #ffffff;
            background: #dc3545;
        }

        .employee-lead-activity-summary-item.is-warning {
            color: #ffffff;
            background: #d97706;
        }

        .employee-lead-activity-summary-item.is-muted {
            color: #ffffff;
            background: #6b7280;
        }

        .employee-lead-activity-summary-item.is-info {
            color: #ffffff;
            background: #2563eb;
        }

        .employee-lead-activity-table-wrap th,
        .employee-lead-activity-table-wrap td {
            white-space: nowrap;
            vertical-align: middle;
            padding: .55rem .65rem;
        }

        .employee-lead-activity-pair {
            display: grid;
            grid-template-columns: minmax(88px, 1fr) minmax(88px, 1fr);
            min-width: 176px;
        }

        .employee-lead-activity-pair span {
            overflow: hidden;
            padding: 0 .45rem;
            text-overflow: ellipsis;
        }

        .employee-lead-table-value {
            display: inline-block;
            border-radius: 4px;
            padding-top: .08rem !important;
            padding-bottom: .08rem !important;
            font-weight: 600;
        }

        .employee-lead-table-value.is-success {
            color: #ffffff;
            background: #198754;
        }

        .employee-lead-table-value.is-danger {
            color: #ffffff;
            background: #dc3545;
        }

        .employee-lead-table-value.is-warning {
            color: #ffffff;
            background: #d97706;
        }

        .employee-lead-table-value.is-muted,
        .employee-lead-table-value.is-neutral {
            color: #ffffff;
            background: #6b7280;
        }

        .employee-lead-table-value.is-info {
            color: #ffffff;
            background: #2563eb;
        }

        .employee-lead-table-value.is-category {
            color: #ffffff;
            background: #0891b2;
        }

        .employee-lead-table-value.is-date-current {
            color: #ffffff;
            background: #15803d;
        }

        .employee-lead-table-value.is-date-previous {
            color: #ffffff;
            background: #6b7280;
        }

        .employee-lead-activity-pair span + span {
            border-left: 1px solid #e5e7eb;
        }

        @media (max-width: 767.98px) {
            .employee-lead-updates-dialog {
                width: calc(100vw - 1rem);
                margin: .5rem auto;
            }

            .employee-lead-updates-dialog .modal-content {
                aspect-ratio: auto;
                max-height: calc(100vh - 1rem);
            }
        }
    </style>
@endpush

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="datatableRange2" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Employee</p>
            <div class="select-status">
                <select class="form-control select-picker" name="employee" id="employee_id" data-live-search="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($employees as $employee)
                        <x-user-option :user="$employee" />
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Lead Source</p>
            <div class="select-status">
                <select class="form-control select-picker" name="source_id" id="source_id" data-live-search="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->id }}">{{ $source->type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Lead Status</p>
            <div class="select-status">
                <select class="form-control select-picker" name="status_id" id="status_id" data-live-search="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($statuses as $status)
                        <option data-content="<i class='fa fa-circle mr-2' style='color:{{ $status->label_color }}'></i> {{ $status->type }}" value="{{ $status->id }}">
                            {{ $status->type }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <p class="text-muted mb-2">Leads Worked counts each lead once across detail changes, follow-ups and notes. Lead Details Updated counts lead fields; Follow-up Updated Leads counts follow-up changes. Action columns count every saved action in the selected date range. Open View for the lead-by-lead change history.</p>
        <div class="d-flex flex-column w-tables rounded mt-4 bg-white table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script type="text/javascript">
        function initEmployeeLeadDateRange() {
            const start = moment().clone().startOf('day');
            const end = moment();

            $('#datatableRange2').daterangepicker({
                locale: daterangeLocale,
                linkedCalendars: false,
                startDate: start,
                endDate: end,
                ranges: daterangeConfig
            }, cb);
            $('#datatableRange2').val(start.format('{{ company()->moment_date_format }}') + ' @lang("app.to") ' + end.format('{{ company()->moment_date_format }}'));
        }

        const showTable = () => {
            window.LaravelDataTables["employee-lead-report-table"].draw();
        };

        $(function() {
            initEmployeeLeadDateRange();

            $('#datatableRange2').on('apply.daterangepicker', function() {
                $('#reset-filters').removeClass('d-none');
                showTable();
            });
        });
    </script>

    <script>
        $('#employee_id, #source_id, #status_id').on('change keyup', function() {
            const hasFilters = $('#employee_id').val() !== 'all' || $('#source_id').val() !== 'all' || $('#status_id').val() !== 'all';
            $('#reset-filters').toggleClass('d-none', !hasFilters);
            showTable();
        });

        $('#employee-lead-report-table').on('preXhr.dt', function(e, settings, data) {
            const dateRangePicker = $('#datatableRange2').data('daterangepicker');
            let startDate = $('#datatableRange2').val();
            let endDate = null;

            if (startDate === '') {
                startDate = null;
                endDate = null;
            } else if (dateRangePicker) {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            data['startDate'] = startDate;
            data['endDate'] = endDate;
            data['employee'] = $('#employee_id').val();
            data['source_id'] = $('#source_id').val();
            data['status_id'] = $('#status_id').val();
        });

        function employeeActivityRequestData() {
            const dateRangePicker = $('#datatableRange2').data('daterangepicker');

            return {
                startDate: dateRangePicker ? dateRangePicker.startDate.format('{{ company()->moment_date_format }}') : '',
                endDate: dateRangePicker ? dateRangePicker.endDate.format('{{ company()->moment_date_format }}') : '',
                employee: $('#employee_id').val(),
                source_id: $('#source_id').val(),
                status_id: $('#status_id').val()
            };
        }

        $('body').off('click.employeeStatusChanges').on('click.employeeStatusChanges', '.js-view-status-changes', function() {
            const url = $(this).data('url');
            const $modal = $(MODAL_LG);
            const $modalBody = $modal.find('.modal-body');
            const $saveButton = $modal.find('.modal-footer .btn-primary');
            const $modalDialog = $modal.find('.modal-dialog');
            const requestData = employeeActivityRequestData();

            $modal.find(MODAL_HEADING).html('Lead Activity');
            $modalBody.html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin mr-2"></i>Loading...</div>');
            $modalDialog.addClass('employee-lead-updates-dialog');
            $saveButton.addClass('d-none');
            $modal.one('hidden.bs.modal', function() {
                $modalDialog.removeClass('employee-lead-updates-dialog');
                $saveButton.removeClass('d-none');
            });
            $modal.modal('show');

            $.ajax({
                url: url + '?' + $.param(requestData),
                type: 'GET',
                success: function(response) {
                    if (response.title) {
                        $modal.find(MODAL_HEADING).html(response.title);
                    }

                    $modalBody.html(response.html || '<div class="text-center py-4 text-muted">No lead updates found in the selected date range.</div>');
                },
                error: function() {
                    $modalBody.html('<div class="alert alert-danger mb-0">Lead updates could not be loaded.</div>');
                }
            });
        });

        $('body').off('click.employeeActivityLoadMore').on('click.employeeActivityLoadMore', '.js-load-more-lead-activity', function() {
            const $button = $(this);
            const $activity = $button.closest('.employee-lead-activity');
            const requestData = employeeActivityRequestData();
            requestData.page = Number($button.data('page')) || 1;
            requestData.per_page = 25;
            requestData.append = 1;

            $button.prop('disabled', true).text('Loading...');

            $.ajax({
                url: $button.data('url') + '?' + $.param(requestData),
                type: 'GET',
                success: function(response) {
                    $activity.find('.employee-lead-activity-rows').append(response.html || '');

                    if (response.has_more) {
                        $button.data('page', response.next_page).prop('disabled', false).text('Load More');
                    } else {
                        $button.remove();
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Load More');
                }
            });
        });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            initEmployeeLeadDateRange();
            $('.filter-box .select-picker').selectpicker('refresh');
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#reset-filters-2').click(function() {
            $('#filter-form')[0].reset();
            initEmployeeLeadDateRange();
            $('.filter-box .select-picker').selectpicker('refresh');
            $('#reset-filters').addClass('d-none');
            showTable();
        });
    </script>
@endpush
