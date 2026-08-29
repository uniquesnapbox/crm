@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">From / To Date</p>
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
                                <div class="text-muted f-12 mr-2">Converted Leads</div>
                                <div id="conversion-total-converted-leads" class="f-22 font-weight-bold text-success">{{ number_format($summary['converted_leads'] ?? 0) }}</div>
                            </div>

                            <div class="d-flex align-items-baseline">
                                <div class="text-muted f-12 mr-2">Revenue</div>
                                <div id="conversion-total-revenue" class="f-22 font-weight-bold">{{ currency_format($summary['revenue'] ?? 0, company()->currency_id) }}</div>
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
        const conversionNumberFormatter = new Intl.NumberFormat('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const conversionCurrencySymbol = @json(company()->currency->currency_symbol ?? '');

        function initConversionDateRange() {
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

        function formatConversionCurrency(amount) {
            return conversionCurrencySymbol + conversionNumberFormatter.format(Number(amount || 0));
        }

        function updateConversionSummary(summary) {
            if (!summary) {
                return;
            }

            $('#conversion-total-converted-leads').text(new Intl.NumberFormat('en-IN').format(Number(summary.converted_leads || 0)));
            $('#conversion-total-revenue').text(formatConversionCurrency(summary.revenue || 0));
        }

        const showTable = () => {
            window.LaravelDataTables["lead-conversion-report-table"].draw(false);
        };

        $(function() {
            initConversionDateRange();

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

        $('#lead-conversion-report-table').on('preXhr.dt', function(e, settings, data) {
            const dateRangePicker = $('#datatableRange2').data('daterangepicker');
            let fromDate = $('#datatableRange2').val();
            let toDate = null;

            if (fromDate === '') {
                fromDate = null;
                toDate = null;
            } else if (dateRangePicker) {
                fromDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                toDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            data['fromDate'] = fromDate;
            data['toDate'] = toDate;
            data['employee'] = $('#employee_id').val();
            data['source_id'] = $('#source_id').val();
            data['status_id'] = $('#status_id').val();
        });

        $('#lead-conversion-report-table').on('xhr.dt', function(e, settings, json) {
            updateConversionSummary(json && json.summary ? json.summary : null);
        });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            initConversionDateRange();
            $('.filter-box .select-picker').selectpicker('refresh');
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#reset-filters-2').click(function() {
            $('#filter-form')[0].reset();
            initConversionDateRange();
            $('.filter-box .select-picker').selectpicker('refresh');
            $('#reset-filters').addClass('d-none');
            showTable();
        });
    </script>
@endpush
