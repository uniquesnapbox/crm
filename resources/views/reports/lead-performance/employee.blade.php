@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
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
        <div class="row mb-4">
            <div class="col-12">
                <x-cards.data>
                    <div class="d-flex flex-nowrap align-items-center justify-content-between" style="white-space: nowrap; overflow-x: auto;">
                        <div class="d-flex flex-nowrap align-items-center">
                            <div class="d-flex align-items-baseline mr-4">
                                <div class="text-muted f-12 mr-2">Total Leads Added</div>
                                <div id="employee-total-leads-added" class="f-22 font-weight-bold">{{ number_format($summary['total_leads_added'] ?? 0) }}</div>
                            </div>

                            <div class="d-flex align-items-baseline mr-4">
                                <div class="text-muted f-12 mr-2">Converted Leads</div>
                                <div id="employee-converted-leads" class="f-22 font-weight-bold text-success">{{ number_format($summary['converted_leads'] ?? 0) }}</div>
                            </div>

                            <div class="d-flex align-items-baseline mr-4">
                                <div class="text-muted f-12 mr-2">Lost Leads</div>
                                <div id="employee-lost-leads" class="f-22 font-weight-bold text-danger">{{ number_format($summary['lost_leads'] ?? 0) }}</div>
                            </div>

                            <div class="d-flex align-items-baseline mr-4">
                                <div class="text-muted f-12 mr-2">Active Leads</div>
                                <div id="employee-active-leads" class="f-22 font-weight-bold text-primary">{{ number_format($summary['active_leads'] ?? 0) }}</div>
                            </div>

                            <div class="d-flex align-items-baseline">
                                <div class="text-muted f-12 mr-2">Conversion %</div>
                                <div id="employee-conversion-percentage" class="f-22 font-weight-bold">{{ number_format((float) ($summary['conversion_percentage'] ?? 0), 2) }}%</div>
                            </div>
                        </div>

                        <div id="table-actions" class="ml-4 flex-shrink-0 d-flex align-items-center"></div>
                    </div>
                </x-cards.data>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-4 bg-white table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script type="text/javascript">
        const employeeNumberFormatter = new Intl.NumberFormat('en-IN');

        function initEmployeeLeadDateRange() {
            const start = moment().clone().startOf('month');
            const end = moment();

            $('#datatableRange2').daterangepicker({
                locale: daterangeLocale,
                linkedCalendars: false,
                startDate: start,
                endDate: end,
                ranges: daterangeConfig
            }, cb);
        }

        function updateEmployeeSummary(summary) {
            if (!summary) {
                return;
            }

            $('#employee-total-leads-added').text(employeeNumberFormatter.format(Number(summary.total_leads_added || 0)));
            $('#employee-converted-leads').text(employeeNumberFormatter.format(Number(summary.converted_leads || 0)));
            $('#employee-lost-leads').text(employeeNumberFormatter.format(Number(summary.lost_leads || 0)));
            $('#employee-active-leads').text(employeeNumberFormatter.format(Number(summary.active_leads || 0)));
            $('#employee-conversion-percentage').text(Number(summary.conversion_percentage || 0).toFixed(2) + '%');
        }

        const showTable = () => {
            window.LaravelDataTables["employee-lead-report-table"].draw(false);
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

        $('#employee-lead-report-table').on('xhr.dt', function(e, settings, json) {
            updateEmployeeSummary(json && json.summary ? json.summary : null);
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
