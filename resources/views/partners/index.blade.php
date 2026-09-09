@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Partners</h4>
            <p class="text-muted mb-0">Manage partner sales and commissions.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('partners.reports') }}" class="btn btn-light"><i class="fa fa-chart-bar mr-1"></i> Reports</a>
            @if(in_array($addPartnerPermission ?? user()->permission('add_partner'), ['all', 'added', 'both']))
                <a href="{{ route('partners.create') }}" class="btn btn-primary"><i class="fa fa-plus mr-1"></i> Add Partner</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded shadow-sm p-3 mb-3">
        <div class="row align-items-center">
            <div class="col-md-3">
                <select id="partner-status-filter" class="form-control">
                    <option value="all">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-5">
                <input id="partner-search" class="form-control" placeholder="Search partner, company, mobile or email">
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow-sm p-3">
        {!! $dataTable->table(['class' => 'table table-hover w-100'], true) !!}
    </div>
</div>
@endsection

@push('scripts')
    @include('sections.datatable_js')
    {!! $dataTable->scripts() !!}
    <script>
        $(function () {
            const table = window.LaravelDataTables['partners-table'];
            $('#partner-status-filter').on('change', function () {
                table.ajax.url('{{ route('partners.index') }}?status=' + encodeURIComponent(this.value)).load();
            });
            $('#partner-search').on('keyup', function () {
                table.search(this.value).draw();
            });
        });
    </script>
@endpush
