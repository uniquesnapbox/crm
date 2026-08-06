<?php

namespace App\DataTables;

use App\Helper\Common;
use App\Models\LeadStatus;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Scopes\ActiveScope;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class LeadContactDataTable extends BaseDataTable
{

    private $editLeadPermission;
    private $viewLeadFollowUpPermission;
    private $deleteLeadPermission;
    private $addFollowUpPermission;
    private $changeLeadStatusPermission;
    private $viewLeadPermission;
    private $employees;

    /**
     * @var LeadStatus[]|\Illuminate\Database\Eloquent\Collection
     */
    private $status;

    public function __construct()
    {
        parent::__construct();
        $this->editLeadPermission = user()->permission('edit_lead');
        $this->deleteLeadPermission = user()->permission('delete_lead');
        $this->viewLeadPermission = user()->permission('view_lead');
        $this->addFollowUpPermission = user()->permission('add_lead_follow_up');
        $this->changeLeadStatusPermission = user()->permission('change_deal_stages');
        $this->viewLeadFollowUpPermission = user()->permission('view_lead_follow_up');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {

        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('check', fn($row) => $this->checkBox($row));
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="d-flex align-items-center justify-content-end lead-table-actions">';
            $action .= '<a href="' . route('lead-contact.show', [$row->id]) . '" class="btn btn-sm btn-outline-secondary mr-1" title="' . __('app.view') . '"><i class="fa fa-eye"></i></a>';

            if (
                $this->editLeadPermission == 'all'
                || ($this->editLeadPermission == 'added' && user()->id == $row->added_by)
                || ($this->editLeadPermission == 'owned' && user()->id == $row->assigned_to)
                || ($this->editLeadPermission == 'both' && (user()->id == $row->added_by || user()->id == $row->assigned_to))
                || user()->id == $row->added_by
                || user()->id == $row->assigned_to)

            {
                $action .= '<a class="btn btn-sm btn-outline-primary mr-1 openRightModal" href="' . route('lead-contact.edit', [$row->id]) . '" title="' . __('app.edit') . '">
                                <i class="fa fa-edit"></i>
                            </a>';
            }

            if (
                $this->deleteLeadPermission == 'all'
                || ($this->deleteLeadPermission == 'added' && user()->id == $row->added_by)
                || ($this->deleteLeadPermission == 'owned' && user()->id == $row->assigned_to)
                || ($this->deleteLeadPermission == 'both' && (user()->id == $row->assigned_to || user()->id == $row->added_by))
            ) {
                $action .= '<a class="btn btn-sm btn-outline-danger delete-table-row" href="javascript:;" data-id="' . $row->id . '" title="' . __('app.delete') . '">
                        <i class="fa fa-trash"></i>
                    </a>';
            }

            $action .= '</div>';

            return $action;
        });

        $datatables->addColumn('export_email', fn($row) => $row->client_email);
        $datatables->addColumn('lead_value', fn($row) => currency_format($row->value, $row->currency_id));
        $datatables->addColumn('name', fn($row) => $row->client_name);
        $datatables->addColumn('added_by', fn($row) => $row->addedBy->name ?? '--');
        $datatables->addColumn('assigned_to', fn($row) => $this->renderAssignedToColumn($row));
        $datatables->addColumn('email', fn($row) => $row->client_email);
        $datatables->addColumn('mobile', fn($row) => $row->mobile ?: '--');
        $datatables->addColumn('category_name', fn($row) => $row->category?->category_name);
        $datatables->addColumn('lead_status', fn($row) => $this->renderLeadStatusColumn($row));
        $datatables->addColumn('interest_level', fn($row) => $this->renderInterestLevelColumn($row));

        $datatables->editColumn('client_name', function ($row) {
            if ($row->client_id != null && $row->client_id != '') {
                $label = '<label class="badge badge-secondary">' . __('app.client') . '</label>';
            }
            else {
                $label = '';
            }

            $client_name = $row->client_name;

            return '
                        <div class="media-body">
                    <h5 class="mb-0 f-13 "><a href="' . route('lead-contact.show', [$row->id]) . '">' . $client_name . '</a></h5>
                    <p class="mb-0">' . $label . '</p>
                    <p class="mb-0 f-12 text-dark-grey">
                    '.$row->company_name.'
                </p>
                    </div>
                  ';
        });

        $datatables->editColumn('created_at', fn($row) => $row->created_at?->translatedFormat($this->company->date_format));
        $datatables->smart(false);
        $datatables->setRowId(fn($row) => 'row-' . $row->id);
        $datatables->setRowClass('lead-table-row');
        $datatables->removeColumn('client_id');
        $datatables->removeColumn('source');

        $customFieldColumns = CustomField::customFieldData($datatables, Lead::CUSTOM_FIELD_MODEL);

        $datatables->rawColumns(array_merge(['action', 'client_name', 'check', 'lead_status', 'interest_level', 'assigned_to'], $customFieldColumns));

        return $datatables;
    }

    /**
     * @param Lead $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Lead $model)
    {
        $leadContact = $model->with([
            'category:id,category_name',
            'addedBy' => fn ($query) => $query
                ->without(['clientDetails', 'employeeDetail', 'leaves', 'roles'])
                ->select('id', 'name', 'company_id'),
            'assignedTo' => fn ($query) => $query
                ->without(['clientDetails', 'employeeDetail', 'leaves', 'roles'])
                ->select('id', 'name', 'company_id'),
        ])
            ->select(
                'leads.id',
                'leads.added_by',
                'leads.assigned_to',
                'leads.client_id',
                'leads.category_id',
                'leads.client_name',
                'leads.client_email',
                'leads.mobile',
                'leads.status_id',
                'leads.interest_level',
                'leads.contact_status',
                'leads.company_name',
                'leads.created_at',
                'leads.updated_at',
                'lead_sources.type as source',
                'lead_status.type as lead_status_type',
                'lead_status.label_color as lead_status_color'
            )
            ->leftJoin('lead_sources', 'lead_sources.id', 'leads.source_id')
            ->leftJoin('lead_status', 'lead_status.id', 'leads.status_id');
        $leadContact = $leadContact->whereNull('leads.archived_at');

        if (!in_array('admin', user_roles())) {
            $leadContact = $leadContact->where(function ($query) {
                $query->where('leads.added_by', user()->id)
                    ->orWhere('leads.assigned_to', user()->id);
            });
        }

        if ($this->request()->type != 'all' && $this->request()->type != '') {

            if ($this->request()->type == 'lead') {
                $leadContact = $leadContact->whereNull('client_id');
            }
            else {
                $leadContact = $leadContact->whereNotNull('client_id');
            }
        }

        if ($this->request()->startDate !== null && $this->request()->startDate != 'null' && $this->request()->startDate != '' && request()->date_filter_on == 'created_at') {
            $startDate = companyToDateString($this->request()->startDate);

            $leadContact = $leadContact->where('leads.created_at', '>=', $startDate . ' 00:00:00');
        }

        if ($this->request()->endDate !== null && $this->request()->endDate != 'null' && $this->request()->endDate != '' && request()->date_filter_on == 'created_at') {
            $endDate = companyToDateString($this->request()->endDate);
            $leadContact = $leadContact->where('leads.created_at', '<=', $endDate . ' 23:59:59');
        }


        if ($this->request()->startDate !== null && $this->request()->startDate != 'null' && $this->request()->startDate != '' && request()->date_filter_on == 'updated_at') {
            $startDate = companyToDateString($this->request()->startDate);
            $leadContact = $leadContact->where('leads.updated_at', '>=', $startDate . ' 00:00:00');
        }

        if ($this->request()->endDate !== null && $this->request()->endDate != 'null' && $this->request()->endDate != '' && request()->date_filter_on == 'updated_at') {
            $endDate = companyToDateString($this->request()->endDate);
            $leadContact = $leadContact->where('leads.updated_at', '<=', $endDate . ' 23:59:59');
        }

        if ($this->request()->category_id != 'all' && $this->request()->category_id != '') {
            $leadContact = $leadContact->where('category_id', $this->request()->category_id);
        }

        if ($this->request()->source_id != 'all' && $this->request()->source_id != '') {
            $leadContact = $leadContact->where('source_id', $this->request()->source_id);
        }

        if ($this->request()->status_id != 'all' && $this->request()->status_id != '') {
            $leadContact = $leadContact->where('leads.status_id', $this->request()->status_id);
        }

        if ($this->request()->interest_level != 'all' && $this->request()->interest_level != '') {
            $leadContact = $leadContact->where('leads.interest_level', $this->request()->interest_level);
        }

        if ($this->viewLeadPermission == 'all' && $this->request()->filter_addedBy != 'all' && $this->request()->filter_addedBy != '') {
            $leadContact = $leadContact->where('leads.added_by', $this->request()->filter_addedBy);
        }

        if ($this->request()->filter_assignedTo != 'all' && $this->request()->filter_assignedTo != '') {
            $leadContact = $leadContact->where('leads.assigned_to', $this->request()->filter_assignedTo);
        }
        
        if ($this->request()->searchText != '') {
            $leadContact = $leadContact->where(function ($query) {
                $query->where('leads.client_name', 'like', '%' . request('searchText') . '%')
                    ->orWhere('leads.client_email', 'like', '%' . request('searchText') . '%')
                    ->orwhere('leads.mobile', 'like', '%' . request('searchText') . '%');
            });
        }

        return $leadContact->groupBy('leads.id');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('lead-contact-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["lead-contact-table"].buttons().container()
                    .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    });
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {

        $data = [

            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false
            ],
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'],
            __('app.id') => ['data' => 'id', 'name' => 'id', 'title' => __('app.id'), 'visible' => showId()],
            __('app.name') => ['data' => 'name', 'name' => 'name', 'exportable' => false, 'visible' => false,'title' => __('app.name')],
            __('modules.leadContact.contactName') => ['data' => 'client_name', 'name' => 'leads.client_name', 'exportable' => true, 'title' => __('modules.leadContact.contactName')],
            __('modules.lead.mobile') => ['data' => 'mobile', 'name' => 'leads.mobile', 'exportable' => true, 'title' => __('modules.lead.mobile')],
            __('modules.lead.companyName') => ['data' => 'company_name', 'name' => 'company_name', 'exportable' => true, 'title' => __('modules.lead.companyName')],
            __('modules.lead.leadStatus') => ['data' => 'lead_status', 'name' => 'lead_status.type', 'title' => __('modules.lead.leadStatus')],
            __('Interest Level') => ['data' => 'interest_level', 'name' => 'leads.interest_level', 'title' => 'Interest Level'],
            __('app.email') . ' ' . __('modules.lead.email') => ['data' => 'export_email', 'name' => 'leads.client_email', 'title' => __('app.lead') . ' ' . __('modules.lead.email'), 'exportable' => true, 'visible' => false],
            __('modules.lead.leadCategory') => ['data' => 'category_name', 'name' => 'category_name', 'exportable' => true, 'visible' => false, 'title' => __('modules.lead.leadCategory')],
            __('app.addedBy') => ['data' => 'added_by', 'name' => 'added_by', 'exportable' => true, 'title' => __('app.addedBy')],
            __('modules.tasks.assignTo') => ['data' => 'assigned_to', 'name' => 'leads.assigned_to', 'exportable' => false, 'title' => __('modules.tasks.assignTo')],
            __('app.createdOn') => ['data' => 'created_at', 'name' => 'leads.created_at', 'title' => __('app.createdOn')],
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];


        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Lead()), $action);

    }

    private function canInlineEdit($row): bool
    {
        return $this->editLeadPermission == 'all'
            || ($this->editLeadPermission == 'added' && user()->id == $row->added_by)
            || ($this->editLeadPermission == 'owned' && user()->id == $row->assigned_to)
            || ($this->editLeadPermission == 'both' && (user()->id == $row->added_by || user()->id == $row->assigned_to))
            || user()->id == $row->added_by
            || user()->id == $row->assigned_to;
    }

    private function renderLeadStatusColumn($row): string
    {
        if (!$this->canInlineEdit($row)) {
            $leadStatus = trim((string) ($row->lead_status_type ?? ''));
            $contactStatus = trim((string) ($row->contact_status ?? ''));

            if ($leadStatus !== '') {
                $statusText = e($leadStatus);
                $labelColor = $row->lead_status_color ?: '#4f6fad';
            } else {
                $mapped = match ($contactStatus) {
                    'connected' => ['Connected', '#16a34a'],
                    'not_connected' => ['Not Connected', '#dc2626'],
                    'pending' => ['Pending', '#ca8a04'],
                    default => ['Not Set', '#8f9bb3'],
                };

                $statusText = e($mapped[0]);
                $labelColor = $mapped[1];
            }

            return '<span class="badge" style="background:' . e($labelColor) . '; color:#fff; font-weight:600; border-radius:999px; padding:3px 10px;">' . $statusText . '</span>';
        }

        $url = route('lead-contact.quick_update', $row->id);
        $selectedValue = (string) ($row->status_id ?? '');
        $options = '<option value="">Not Set</option>';

        foreach ($this->statuses() as $status) {
            $statusId = (string) $status->id;
            $selected = $selectedValue === $statusId ? ' selected' : '';
            $options .= '<option value="' . e($statusId) . '"' . $selected . '>' . e($status->type) . '</option>';
        }

        return '<div class="lead-inline-select-wrap">' .
            '<select class="form-control form-control-sm js-lead-table-inline-select" style="min-width:140px;" data-field="status_id" data-prev-value="' . e($selectedValue) . '" data-url="' . e($url) . '" data-id="' . (int) $row->id . '">' .
            $options .
            '</select>' .
            '</div>';
    }

    private function renderInterestLevelColumn($row): string
    {
        $interestLevel = trim((string) ($row->interest_level ?? ''));

        if (!$this->canInlineEdit($row)) {
            if ($interestLevel === '') {
                return '<span class="badge badge-light" style="border-radius:999px; padding:3px 10px;">--</span>';
            }

            $mapped = match ($interestLevel) {
                'low' => ['Low', '#64748b'],
                'medium' => ['Medium', '#2563eb'],
                'high' => ['High', '#ea580c'],
                'very_high' => ['Very High', '#16a34a'],
                default => [str($interestLevel)->replace('_', ' ')->title(), '#4f6fad'],
            };

            return '<span class="badge" style="background:' . e($mapped[1]) . '; color:#fff; font-weight:600; border-radius:999px; padding:3px 10px;">' . e($mapped[0]) . '</span>';
        }

        $url = route('lead-contact.quick_update', $row->id);
        $selectedValue = $interestLevel;
        $options = [
            '' => '--',
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'very_high' => 'Very High',
        ];

        $htmlOptions = '';
        foreach ($options as $value => $label) {
            $selected = $selectedValue === $value ? ' selected' : '';
            $htmlOptions .= '<option value="' . e($value) . '"' . $selected . '>' . e($label) . '</option>';
        }

        return '<div class="lead-inline-select-wrap">' .
            '<select class="form-control form-control-sm js-lead-table-inline-select" style="min-width:120px;" data-field="interest_level" data-prev-value="' . e($selectedValue) . '" data-url="' . e($url) . '" data-id="' . (int) $row->id . '">' .
            $htmlOptions .
            '</select>' .
            '</div>';
    }

    private function renderAssignedToColumn($row): string
    {
        if (!$this->canInlineEdit($row)) {
            return e($row->assignedTo->name ?? '--');
        }

        $url = route('lead-contact.quick_update', $row->id);
        $selectedValue = (string) ($row->assigned_to ?? '');
        $options = '<option value="">Unassigned</option>';

        foreach ($this->assignableEmployees() as $employee) {
            $employeeId = (string) $employee->id;
            $selected = $selectedValue === $employeeId ? ' selected' : '';
            $options .= '<option value="' . e($employeeId) . '"' . $selected . '>' . e($employee->name) . '</option>';
        }

        return '<div class="lead-inline-select-wrap">' .
            '<select class="form-control form-control-sm js-lead-table-inline-select" style="min-width:150px;" data-field="assigned_to" data-prev-value="' . e($selectedValue) . '" data-url="' . e($url) . '" data-id="' . (int) $row->id . '">' .
            $options .
            '</select>' .
            '</div>';
    }

    private function statuses()
    {
        if (is_null($this->status)) {
            $this->status = LeadStatus::query()->select('id', 'type')->orderBy('priority')->get();
        }

        return $this->status;
    }

    private function assignableEmployees()
    {
        if (is_null($this->employees)) {
            $users = User::without(['clientDetails', 'employeeDetail', 'leaves', 'roles'])
                ->withRole('employee')
                ->join('employee_details', 'employee_details.user_id', '=', 'users.id')
                ->select('users.id', 'users.name')
                ->where('users.status', 'active');

            $viewEmployeePermission = user()->permission('view_employees');

            if (($viewEmployeePermission == 'added' || $viewEmployeePermission == 'both') && !in_array('client', user_roles())) {
                $users->where(function ($query) {
                    $query->where('employee_details.user_id', user()->id)
                        ->orWhere('employee_details.added_by', user()->id);
                });
            }
            elseif (($viewEmployeePermission == 'owned' || $viewEmployeePermission == 'none' || $viewEmployeePermission == '') && !in_array('client', user_roles())) {
                $users->where('users.id', user()->id);
            }

            if (in_array('client', user_roles())) {
                $clientEmployees = Project::where('client_id', user()->id)
                    ->join('project_members', 'project_members.project_id', '=', 'projects.id')
                    ->select('project_members.user_id')
                    ->get()
                    ->pluck('user_id');

                $users->whereIn('users.id', $clientEmployees);
            }

            $this->employees = $users
                ->orderBy('users.name')
                ->groupBy('users.id')
                ->get();
        }

        return $this->employees;
    }

}
