<script src="{{ asset('vendor/jquery/frappe-charts.min.iife.js') }}"></script>
<script src="{{ asset('vendor/jquery/Chart.min.js') }}"></script>
<style>
    .pipeline {
        width: 20% !important;
    }
</style>

<div class="row">
    @if (in_array('clients', user_modules()) && in_array('total_clients', $activeWidgets))
        <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
            <a href="javascript:;" id="totalClients">
                <x-cards.widget :title="__('modules.dashboard.totalClients')" :value="$totalClient" icon="users"/>
            </a>
        </div>
    @endif

    @if (in_array('leads', user_modules()) && in_array('total_leads', $activeWidgets))
        <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
            <a href="javascript:;" id="totalLeads">
                <x-cards.widget :title="__('modules.dashboard.totalLeads')" :value="$totalLead" icon="users"/>
            </a>
        </div>
    @endif

    @if (in_array('contracts', user_modules()) && in_array('total_contracts_generated', $activeWidgets))
        <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
            <a href="javascript:;" id="totalContractsGenerated">
                <x-cards.widget :title="__('modules.dashboard.totalContractsGenerated')"
                                :value="$totalContractsGenerated" icon="file-contract"/>
            </a>
        </div>
    @endif

    @if (in_array('contracts', user_modules()) && in_array('total_contracts_signed', $activeWidgets))
        <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
            <a href="javascript:;" id="totalContractsSigned">
                <x-cards.widget :title="__('modules.dashboard.totalContractsSigned')" :value="$totalContractsSigned"
                                icon="file-signature"/>
            </a>
        </div>
    @endif

</div>

<div class="row">
    @if (in_array('payments', user_modules()) && in_array('client_wise_earnings', $activeWidgets))
        <div class="col-sm-12 col-lg-6 mt-3">
            <x-cards.data
                :title="__('modules.dashboard.clientWiseEarnings').' <i class=\'fa fa-question-circle\' data-toggle=\'popover\' data-placement=\'top\' data-content=\''.__('messages.earningChartNote').'\' data-trigger=\'hover\'></i>'">
                <x-bar-chart id="task-chart1" :chartData="$clientEarningChart" height="300"/>
            </x-cards.data>
        </div>
    @endif

    @if (in_array('timelogs', user_modules()) && in_array('client_wise_timelogs', $activeWidgets))
        <div class="col-sm-12 col-lg-6 mt-3">
            <x-cards.data :title="__('modules.dashboard.clientWiseTimelogs')">
                <x-line-chart id="task-chart2" :chartData="$clientTimelogChart" height="300"/>
            </x-cards.data>
        </div>
    @endif

    @if (in_array('leads', user_modules()) && in_array('lead_vs_source', $activeWidgets))
        <div class="col-sm-12 col-lg-6 mt-3">
            <x-cards.data :title="__('modules.dashboard.leadVsSource')">
                <x-pie-chart id="task-chart4" :labels="$leadSourceChart['labels']" :values="$leadSourceChart['values']"
                             :colors="$leadSourceChart['colors']" height="300" width="300"/>
            </x-cards.data>
        </div>
    @endif

    @if (in_array('clients', user_modules()) && in_array('latest_client', $activeWidgets))
        <div class="col-sm-12 col-lg-6 mt-3">
            <x-cards.data :title="__('modules.dashboard.latestClient')" padding="false" otherClasses="h-200">
                <x-table class="border-0 pb-3 admin-dash-table table-hover">
                    <x-slot name="thead">
                        <th class="pl-20">@lang('app.client')</th>
                        <th>@lang('app.email')</th>
                        <th class="pr-20 text-right">@lang('app.createdOn')</th>
                    </x-slot>
                    @forelse ($latestClient->users as $item)
                        <tr>
                            <td class="pl-20">
                                <x-client :user="$item"/>
                            </td>
                            <td>
                                {{ $item->email }}
                            </td>
                            <td class="pr-20"
                                align="right">{{ $item->created_at->translatedFormat(company()->date_format) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="shadow-none">
                                <x-cards.no-record icon="ticket-alt" :message="__('messages.noRecordFound')"/>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-cards.data>
        </div>
    @endif

    @if (in_array('clients', user_modules()) && in_array('recent_login_activities', $activeWidgets))
        <div class="col-sm-12 col-lg-6 mt-3">
            <x-cards.data :title="__('modules.dashboard.recentLoginActivities')" padding="false" otherClasses="h-200">
                <x-table class="border-0 pb-3 admin-dash-table table-hover">

                    <x-slot name="thead">
                        <th class="pl-20">@lang('app.client')</th>
                        <th>@lang('app.email')</th>
                        <th class="pr-20 text-right">@lang('app.lastLogin')</th>
                    </x-slot>
                    @forelse ($recentLoginActivities->users as $item)
                        <tr>
                            <td class="pl-20">
                                <x-client :user="$item"/>
                            </td>
                            <td>
                                {{ $item->email }}
                            </td>
                            <td align="right" class="pr-20">
                                {{ $item->last_login ? $item->last_login->timezone(company()->timezone)->translatedFormat(company()->date_format . ' ' . company()->time_format) : '--' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="shadow-none">
                                <x-cards.no-record icon="ticket-alt" :message="__('messages.noRecordFound')"/>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-cards.data>
        </div>
    @endif

</div>

<script>
    $('#save-dashboard-widget').click(function () {
        $.easyAjax({
            url: "{{ route('dashboard.widget', 'admin-client-dashboard') }}",
            container: '#dashboardWidgetForm',
            blockUI: true,
            type: "POST",
            redirect: true,
            data: $('#dashboardWidgetForm').serialize(),
            success: function () {
                window.location.reload();
            }
        })
    });


    $('#totalClients').click(function () {
        var dateRange = getDateRange();
        var url = `{{ route('clients.index') }}`;

        string = `?start=${dateRange.startDate}&end=${dateRange.endDate}`;
        url += string;

        window.location.href = url;
    });

    $('#totalLeads').click(function () {
        var dateRange = getDateRange();
        var url = `{{ route('lead-contact.index') }}`;

        string = `?start=${dateRange.startDate}&end=${dateRange.endDate}`;
        url += string;

        window.location.href = url;
    });

    $('#totalContractsGenerated').click(function () {
        var dateRange = getDateRange();
        var url = `{{ route('contracts.index') }}`;

        string = `?start=${dateRange.startDate}&end=${dateRange.endDate}`;
        url += string;

        window.location.href = url;
    });

    $('#totalContractsSigned').click(function () {
        var dateRange = getDateRange();
        var url = `{{ route('contracts.index') }}`;

        string = `?signed=yes&start=${dateRange.startDate}&end=${dateRange.endDate}`;
        url += string;

        window.location.href = url;
    });

    function getDateRange() {
        var dateRange = $('#datatableRange2').data('daterangepicker');
        var startDate = dateRange.startDate.format('{{ company()->moment_date_format }}');
        var endDate = dateRange.endDate.format('{{ company()->moment_date_format }}');

        startDate = encodeURIComponent(startDate);
        endDate = encodeURIComponent(endDate);
        var pipelineId = $('#pipeline').val();

        var data = [];
        data['startDate'] = startDate;
        data['endDate'] = endDate;
        data['pipelineId'] = pipelineId;

        return data;
    }

</script>
