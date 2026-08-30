<x-filters.filter-box class="lead-contact-toolbar">
    <div id="table-actions" class="d-flex align-items-center flex-nowrap pr-lg-2">
        @php
            $canAddLeadContact = in_array($addLeadPermission ?? null, ['all', 'added'], true);
            $canManageLeadForms = ($addLeadCustomFormPermission ?? null) === 'all';
        @endphp
        @if ($canAddLeadContact)
            <a class="btn btn-primary openRightModal lead-contact-actions-toggle"
                href="{{ route('lead-contact.create') }}">
                <i class="fa fa-plus mr-1"></i> Add Lead
            </a>
        @else
            <button class="btn btn-primary lead-contact-actions-toggle" type="button" disabled>
                <i class="fa fa-plus mr-1"></i> Add Lead
            </button>
        @endif

        <div class="dropdown lead-contact-actions ml-2">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="More lead actions">
                <i class="fa fa-ellipsis-h"></i>
            </button>
            <div class="dropdown-menu border-grey rounded b-shadow-4 py-2">
                <a class="dropdown-item {{ $canManageLeadForms ? '' : 'disabled' }}"
                    href="{{ $canManageLeadForms ? route('lead-form.index') : 'javascript:;' }}"
                    @if (! $canManageLeadForms) aria-disabled="true" tabindex="-1" @endif>
                    <i class="fa fa-pencil-alt mr-2"></i> @lang('modules.lead.leadForm')
                </a>

                <a class="dropdown-item openRightModal {{ $canAddLeadContact ? '' : 'disabled' }}"
                    href="{{ $canAddLeadContact ? route('lead-contact.import') : 'javascript:;' }}"
                    @if (! $canAddLeadContact) aria-disabled="true" tabindex="-1" @endif>
                    <i class="fa fa-file-upload mr-2"></i> @lang('app.importExcel')
                </a>
            </div>
        </div>
    </div>

    <!-- DATE START -->
    <div class="select-box d-flex pr-1 border-right-grey border-right-grey-sm-0">
        <p class="mb-0 pr-1 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
        <div class="select-status d-flex">
            <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                id="datatableRange" placeholder="@lang('placeholders.dateRange')">
        </div>
    </div>
    <!-- DATE END -->

    @php
        $selectedType = request('type') ?: 'lead';
    @endphp

    <!-- CLIENT START -->
    <div class="select-box d-flex py-2 px-lg-1 px-md-1 px-0 border-right-grey border-right-grey-sm-0">
        <p class="mb-0 pr-1 f-14 text-dark-grey d-flex align-items-center">@lang('modules.invoices.type')</p>
        <div class="select-status">
            <select class="form-control select-picker" name="type" id="type">
                <option {{ $selectedType == 'lead' ? 'selected' : '' }} value="lead">@lang('modules.lead.lead')
                </option>
                <option {{ $selectedType == 'client' ? 'selected' : '' }} value="client">
                    @lang('modules.lead.client')</option>
            </select>
        </div>
    </div>
    <!-- CLIENT END -->

    <!-- SEARCH BY TASK START -->
    <div class="task-search d-flex py-1 px-lg-2 px-0 border-right-grey align-items-center">
        <div class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
            <div class="input-group bg-grey rounded">
                <div class="input-group-prepend">
                    <span class="input-group-text border-0 bg-additional-grey">
                        <i class="fa fa-search f-13 text-dark-grey"></i>
                    </span>
                </div>
                <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                    placeholder="@lang('app.startTyping')">
            </div>
        </div>
    </div>
    <!-- SEARCH BY TASK END -->

    <!-- RESET START -->
    <div class="select-box d-flex py-1 px-lg-1 px-md-1 px-0">
        <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
            @lang('app.clearFilters')
        </x-forms.button-secondary>
    </div>
    <!-- RESET END -->

    <!-- MORE FILTERS START -->
    <x-filters.more-filter-box>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize" for="date_filter_on">@lang('app.dateFilterOn')</label>
            <div class="select-filter mb-4">
                <select class="form-control select-picker" name="date_filter_on" id="date_filter_on">
                    <option value="created_at">@lang('app.createdOn')</option>
                    <option value="updated_at">@lang('app.updatedOn')</option>
                </select>
            </div>
        </div>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize"
                for="filter_category_id">@lang('modules.lead.leadCategory')</label>
            <div class="select-filter mb-4">
                <div class="select-others">
                    <select class="form-control select-picker" id="filter_category_id" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_status_id">@lang('modules.lead.leadStatus')</label>
            <div class="select-filter mb-4">
                <div class="select-others">
                    <select class="form-control select-picker" id="filter_status_id" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_interest_level">Interest Level</label>
            <div class="select-filter mb-4">
                <div class="select-others">
                    <select class="form-control select-picker" id="filter_interest_level" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        <option value="low" data-content="<span><i class='fa fa-circle mr-2' style='color:#64748b'></i>Low</span>">Low</option>
                        <option value="medium" data-content="<span><i class='fa fa-circle mr-2' style='color:#2563eb'></i>Medium</span>">Medium</option>
                        <option value="high" data-content="<span><i class='fa fa-circle mr-2' style='color:#ea580c'></i>High</span>">High</option>
                        <option value="very_high" data-content="<span><i class='fa fa-circle mr-2' style='color:#16a34a'></i>Very High</span>">Very High</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_source_id">@lang('modules.lead.leadSource')</label>
            <div class="select-filter mb-4">
                <div class="select-others">
                    <select class="form-control select-picker" id="filter_source_id" data-live-search="true" data-container="body" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_addedBy">@lang('app.addedBy')</label>
            <div class="select-filter mb-4">
                <div class="select-others">
                <select class="form-control select-picker" id="filter_addedBy" data-live-search="true" data-container="body" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($employees as $item)
                        <x-user-option :user="$item"  />
                    @endforeach
                </select>
                </div>
            </div>
        </div>

        <div class="more-filter-items">
            <label class="f-14 text-dark-grey mb-12 text-capitalize" for="filter_assigned_to">@lang('modules.tasks.assignTo')</label>
            <div class="select-filter mb-4">
                <div class="select-others">
                <select class="form-control select-picker" id="filter_assigned_to" data-live-search="true" data-container="body" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($employees as $item)
                        <x-user-option :user="$item" />
                    @endforeach
                </select>
                </div>
            </div>
        </div>

    </x-filters.more-filter-box>
</x-filters.filter-box>

<div class="lead-contact-bulk-bar bg-white border-top-grey px-3 py-2 d-none" id="lead-contact-bulk-bar">
    <span class="text-dark-grey f-13 mr-3" id="lead-contact-selected-count"></span>
    <x-datatable.actions>
        <div class="select-status mr-2">
            <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                <option value="">@lang('app.selectAction')</option>
                @if ($canBulkAssignLead)
                    <option value="assign-to">@lang('modules.tasks.assignTo')</option>
                @endif
                <option value="delete">@lang('app.delete')</option>
            </select>
        </div>
        @if ($canBulkAssignLead)
            <div class="select-status mr-2 d-none quick-action-field" id="change-agent-action">
                <select name="assigned_to" id="assigned_to" class="form-control select-picker" data-live-search="true" data-size="8">
                    <option value="">@lang('modules.tasks.assignTo')</option>
                    @foreach ($assignableEmployees ?? $employees as $employee)
                        <x-user-option :user="$employee" />
                    @endforeach
                </select>
            </div>
        @endif
    </x-datatable.actions>
</div>

@push('scripts')
    <script>
        $('#type, #filter_assigned_to, #filter_category_id, #filter_status_id, #filter_interest_level, #filter_source_id, #date_filter_on, #min, #max, #filter_addedBy')
            .on('change keyup', function() {
                if ($('#type').val() != "lead") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#min').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#max').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#filter_category_id').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#filter_source_id').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#filter_status_id').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#filter_interest_level').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#date_filter_on').val() != "created_at") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#filter_addedBy').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else if ($('#filter_assigned_to').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                    showTable();
                } else {
                    $('#reset-filters').addClass('d-none');
                    showTable();
                }
            });

        $('#search-text-field').on('keyup', function() {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $('#reset-filters,#reset-filters-2').click(function() {
            $('#filter-form')[0].reset();

            $('#type').val('lead');
            $('.filter-box #status').val('not finished');
            $('.filter-box #date_filter_on').val('created_at');
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

    </script>
@endpush
