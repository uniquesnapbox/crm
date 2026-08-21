<?php

namespace App\Http\Controllers;

use App\DataTables\LeadFollowupDataTable;
use App\DataTables\LeadContactDataTable;
use App\DataTables\LeadNotesDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Lead\QuickUpdateRequest;
use App\Http\Requests\Lead\StoreRequest;
use App\Http\Requests\Lead\UpdateRequest;
use App\Imports\LeadImport;
use App\Jobs\ImportLeadJob;
use App\Models\ClientNote;
use App\Models\ClientCategory;
use App\Models\LeadCategory;
use App\Models\Lead;
use App\Models\LeadCustomForm;
use App\Models\LeadFollowUp;
use App\Models\LeadFollowUpAttachment;
use App\Models\LeadHistory;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Traits\ImportExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeadContactController extends AccountBaseController
{

    use ImportExcel;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'modules.leadContact.leadContacts';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leads', $this->user->modules));

            return $next($request);
        });
    }

    public function index(LeadContactDataTable $dataTable)
    {
        $this->viewLeadPermission = $viewPermission = user()->permission('view_lead');

        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->categories = LeadCategory::query()->select('id', 'category_name')->orderBy('category_name')->get();
            $this->sources = LeadSource::query()->select('id', 'type')->orderBy('type')->get();
            $this->statuses = LeadStatus::query()->select('id', 'type', 'label_color', 'priority', 'default')->orderBy('priority')->get();
            $this->employees = User::allEmployees();
            $this->assignableEmployees = User::allEmployees(null, true, null, company()->id);
            $this->canBulkAssignLead = $this->canManageLeadAssignment();
        }

        return $dataTable->render('lead-contact.index', $this->data);

    }

    public function show($id)
    {
        $this->leadContact = Lead::with(['leadSource', 'leadStatus', 'category', 'addedBy', 'assignedTo', 'followUps', 'latestFollowUp'])->findOrFail($id)->withCustomFields();

        $this->viewPermission = user()->permission('view_lead');

        abort_403(!($this->viewPermission == 'all' && $this->isAdminUser()) && !$this->canAccessLead($this->leadContact));

        $this->pageTitle = $this->leadContact->client_name; // removed salutation

        $this->categories = LeadCategory::query()->select('id', 'category_name')->orderBy('category_name')->get();
        $this->sources = LeadSource::query()->select('id', 'type')->orderBy('type')->get();
        $this->statuses = LeadStatus::query()->select('id', 'type', 'label_color', 'priority', 'default')->orderBy('priority')->get();
        $this->products = Product::query()->select('id', 'name')->orderBy('name')->get();
        $this->countries = countries();
        $this->canManageLeadAssignment = $this->canManageLeadAssignment();
        if ($this->canManageLeadAssignment) {
            $this->employees = User::allEmployees(null, true, null, company()->id);
        }
        $this->editPermission = user()->permission('edit_lead');
        $this->canInlineEdit = (
            $this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->leadContact->added_by == user()->id)
            || ($this->editPermission == 'owned' && $this->leadContact->assigned_to == user()->id)
            || ($this->editPermission == 'both' && ($this->leadContact->added_by == user()->id || $this->leadContact->assigned_to == user()->id))
            || user()->id == $this->leadContact->added_by
            || user()->id == $this->leadContact->assigned_to
        );

        $this->leadFormFields = LeadCustomForm::with('customField')->where('status', 'active')->where('custom_fields_id', '!=', 'null')->get();

        $this->leadId = $id;

        if ($this->leadContact->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->leadContact->getCustomFieldGroupsWithFields()->fields;
        }

        $this->deleteLeadPermission = user()->permission('delete_lead');

        $tab = request('tab');

        switch ($tab) {
        case 'follow-up':
        case 'notes':
        case 'history':
            return $this->history();
        default:
            $this->view = 'lead-contact.ajax.profile';
            break;
        }

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        $this->activeTab = $tab ?: 'profile';

        return view('lead-contact.show', $this->data);

    }

    public function notes()
    {
        $dataTable = new LeadNotesDataTable();
        $viewPermission = user()->permission('view_lead_note');

        abort_403(!in_array($viewPermission, ['all', 'added', 'both', 'owned']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';

        $this->view = 'lead-contact.ajax.notes';

        return $dataTable->render('lead-contact.show', $this->data);
    }

    public function followUps()
    {
        $viewPermission = user()->permission('view_lead_follow_up');

        abort_403(!in_array($viewPermission, ['all', 'added', 'both', 'owned']));

        $tab = request('tab');
        $this->activeTab = $tab ?: 'profile';
        $this->view = 'lead-contact.ajax.follow-up';

        return (new LeadFollowupDataTable())->render('lead-contact.show', $this->data);
    }

    public function history()
    {
        $historyItems = collect();

        if (Schema::hasTable('lead_histories')) {
            $historyRows = LeadHistory::with('createdBy')
                ->where('lead_id', $this->leadContact->id)
                ->orderByDesc('event_at')
                ->orderByDesc('id')
                ->limit(400)
                ->get();

            $followUpIds = $historyRows
                ->pluck('meta')
                ->filter(fn ($meta) => is_array($meta) && !empty($meta['followup_id']))
                ->map(fn ($meta) => (int) $meta['followup_id'])
                ->unique()
                ->values();
            $followUps = $followUpIds->isEmpty()
                ? collect()
                : LeadFollowUp::with('lead')->whereIn('id', $followUpIds)->get()->keyBy('id');
            $actionableFollowUpIds = [];

            $historyItems = $historyRows->map(function (LeadHistory $row) use ($followUps, &$actionableFollowUpIds) {
                $meta = is_array($row->meta) ? $row->meta : [];
                $followUpId = $meta['followup_id'] ?? null;
                $followUp = $followUpId ? $followUps->get((int) $followUpId) : null;
                // History metadata is an immutable snapshot. For actionable
                // rows always render the live status from lead_follow_up;
                // otherwise an old "Follow-up Added" event keeps showing
                // Pending even after the follow-up was completed.
                $followUpStatus = $followUp?->status
                    ?? ($meta['followup_status'] ?? null);
                $canEditFollowUp = $this->canEditFollowUpRecord($followUp);
                $isLatestFollowUpEntry = false;

                if ($followUpId && !isset($actionableFollowUpIds[(int) $followUpId])) {
                    $actionableFollowUpIds[(int) $followUpId] = true;
                    $isLatestFollowUpEntry = true;
                }

                $type = 'updated';

                if (str_contains((string) $row->event_type, 'created')) {
                    $type = 'created';
                }
                if (str_contains((string) $row->event_type, 'followup')) {
                    $type = 'followup';
                }
                if (str_contains((string) $row->event_type, 'note')) {
                    $type = 'note';
                }

                return [
                    'event_type' => $row->event_type,
                    'type' => $type,
                    'title' => $row->title ?: 'Lead Updated',
                    'description' => $row->description ?: '--',
                    'meta' => 'By ' . (optional($row->createdBy)->name ?: 'System'),
                    'timestamp' => $row->event_at ?: $row->created_at,
                    'followup_id' => $followUpId,
                    'followup_status' => $followUpStatus,
                    'followup_edit_url' => $followUpId ? route('lead-contact.follow_up_edit', $followUpId) : null,
                    'can_update_followup_status' => $canEditFollowUp && $isLatestFollowUpEntry,
                    'can_edit_followup' => $canEditFollowUp && $isLatestFollowUpEntry,
                ];
            });
        }

        $hasCreatedEntry = $historyItems->contains(function ($item) {
            return ($item['event_type'] ?? '') === 'lead_created';
        });

        if (!$hasCreatedEntry) {
            $historyItems->push([
                'event_type' => 'lead_created',
                'type' => 'created',
                'title' => 'Lead Created',
                'description' => $this->leadContact->client_name . ' was added as a lead.',
                'meta' => 'By ' . (optional($this->leadContact->addedBy)->name ?: 'System'),
                'timestamp' => $this->leadContact->created_at,
            ]);
        }

        // Backward compatibility: include legacy follow-ups that were created
        // before lead_histories logging existed.
        $existingFollowUpIds = $historyItems
            ->pluck('followup_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $legacyFollowUps = LeadFollowUp::with('addedBy')
            ->where('lead_id', $this->leadContact->id)
            ->when(!empty($existingFollowUpIds), function ($query) use ($existingFollowUpIds) {
                $query->whereNotIn('id', $existingFollowUpIds);
            })
            ->orderByDesc('created_at')
            ->limit(150)
            ->get();

        foreach ($legacyFollowUps as $followUp) {
            $status = ucfirst((string) ($followUp->status ?: 'pending'));
            $nextDateText = $followUp->next_follow_up_date
                ? ' | Next: ' . $followUp->next_follow_up_date->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format)
                : '';

            $historyItems->push([
                'event_type' => 'followup_legacy',
                'type' => 'followup',
                'title' => 'Follow-up ' . $status,
                'description' => (trim(strip_tags((string) $followUp->remark)) ?: 'Follow-up updated') . $nextDateText,
                'meta' => 'By ' . (optional($followUp->addedBy)->name ?: 'System'),
                'timestamp' => $followUp->updated_at ?: $followUp->created_at ?: $followUp->next_follow_up_date,
                'followup_id' => $followUp->id,
                'followup_status' => $followUp->status ?: 'pending',
                'followup_edit_url' => route('lead-contact.follow_up_edit', $followUp->id),
                'can_update_followup_status' => $this->canEditFollowUpRecord($followUp),
                'can_edit_followup' => $this->canEditFollowUpRecord($followUp),
            ]);
        }

        $this->historyItems = $historyItems
            ->filter(fn ($item) => !empty($item['timestamp']))
            ->sortByDesc('timestamp')
            ->values();

        $tab = request('tab');
        $this->activeTab = $tab ?: 'history';
        $this->view = 'lead-contact.ajax.history';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('lead-contact.show', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->pageTitle = __('modules.leadContact.createTitle');

        $this->addPermission = user()->permission('add_lead');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if ($this->shouldLoadLeadEmployees()) {
            $this->employees = User::allEmployees(null, true, null, company()->id);
        }

        $defaultStatus = LeadStatus::query()->select('id')->where('default', '1')->first();
        $this->columnId = ((request('column_id') != '') ? request('column_id') : $defaultStatus?->id);

        $leadContact = new Lead();

        if ($leadContact->getCustomFieldGroupsWithFields()) {
            $this->fields = $leadContact->getCustomFieldGroupsWithFields()->fields;
        }

        $this->products = Product::query()->select('id', 'name')->orderBy('name')->get();
        $this->sources = LeadSource::query()->select('id', 'type')->orderBy('type')->get();
        $this->status = LeadStatus::query()->select('id', 'type', 'label_color', 'priority', 'default')->orderBy('priority')->get();
        $this->categories = LeadCategory::query()->select('id', 'category_name')->orderBy('category_name')->get();
        $this->countries = countries();
        // salutations removed

        $this->view = 'lead-contact.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('lead-contact.create', $this->data);

    }

    /**
     * @param StoreRequest $request
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(StoreRequest $request)
    {
        $this->addPermission = user()->permission('add_lead');

        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $existingUser = User::select('id')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'client');
            })->where('company_id', company()->id)
            ->where('email', $request->client_email)
            ->whereNotNull('email')
            ->first();

        $leadContact = new Lead();
        $leadContact->company_id = company()->id;
        // salutation removed
        $leadContact->client_name = $request->client_name;
        $leadContact->client_email = $request->client_email;
        if ($request->has('note')) {
            $leadContact->note = trim_editor($request->note);
        }
        $leadContact->source_id = $request->source_id;
        $leadContact->status_id = $request->status_id;
        $leadContact->client_id = $existingUser?->id;
        if ($request->has('company_name')) {
            $leadContact->company_name = $request->company_name;
        }
        $leadContact->website = $request->website;
        $leadContact->address = $request->address;
        if ($request->has('cell')) {
            $leadContact->cell = $request->cell;
        }
        $leadContact->office = $request->office;
        // city, state, postal_code removed
        $leadContact->country = $request->country ?: 'India';
        $leadContact->mobile = $this->normalizeMobileByCountry($request->mobile, $request->country);
        $leadContact->interest_level = $request->interest_level;
        $leadContact->deal_size = $request->deal_size;
        $leadContact->contact_status = $request->contact_status;
        $leadContact->contact_status_reason = $request->contact_status_reason;
        $leadContact->products_services = $request->products_services;
        $leadContact->added_by = $request->added_by ?? user()->id; // save added_by, fallback to current user
        $leadContact->assigned_to = $this->resolvedAssignedTo($request);
        $leadContact->save();

        $followUpCreated = $this->createLeadFormFollowUp($leadContact, $request);

        // To add custom fields data
        if ($request->custom_fields_data) {
            $leadContact->updateCustomFieldData($request->custom_fields_data);
        }

        // Log search
        $this->logSearchEntry($leadContact->id, $leadContact->client_name, 'lead-contact.show', 'lead');

        if ($leadContact->client_email) {
            $this->logSearchEntry($leadContact->id, $leadContact->client_name, 'lead-contact.show', 'lead');
        }

        $redirectUrl = urldecode($request->redirect_url);

        if ($request->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true]);
        }

        if ($request->input('form_action') === 'schedule_follow_up' && $followUpCreated) {
            $redirectUrl = route('lead-contact.show', $leadContact->id) . '?tab=follow-up';
        }
        elseif ($redirectUrl == '') {
            $redirectUrl = route('lead-contact.index');
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->leadContact = Lead::with('leadSource', 'leadStatus', 'category', 'assignedTo')->findOrFail($id)->withCustomFields();

        $this->editPermission = user()->permission('edit_lead');

        abort_403(!$this->canAccessLead($this->leadContact));

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->leadContact->added_by == user()->id)
            || ($this->editPermission == 'owned' && $this->leadContact->assigned_to == user()->id)
            || ($this->editPermission == 'both' && ($this->leadContact->added_by == user()->id || $this->leadContact->assigned_to == user()->id))
            || user()->id == $this->leadContact->added_by)
        );

        if ($this->shouldLoadLeadEmployees()) {
            $this->employees = User::allEmployees(null, true, null, company()->id);
        }

        if ($this->leadContact->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->leadContact->getCustomFieldGroupsWithFields()->fields;
        }

        $this->sources = LeadSource::query()->select('id', 'type')->orderBy('type')->get();
        $this->status = LeadStatus::query()->select('id', 'type', 'label_color', 'priority', 'default')->orderBy('priority')->get();
        $this->categories = LeadCategory::query()->select('id', 'category_name')->orderBy('category_name')->get();
        $this->countries = countries();

        $this->pageTitle = __('modules.leadContact.updateTitle');
        // salutations removed

        if (request()->ajax()) {
            $html = view('lead-contact.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'lead-contact.ajax.edit';

        return view('lead-contact.create', $this->data);

    }

    /**
     * @param UpdateRequest $request
     * @param int $id
     * @return array|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(UpdateRequest $request, $id)
    {
        $leadContact = Lead::findOrFail($id);
        $this->editPermission = user()->permission('edit_lead');

        abort_403(!$this->canAccessLead($leadContact));

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $leadContact->added_by == user()->id)
            || ($this->editPermission == 'owned' && $leadContact->assigned_to == user()->id)
            || ($this->editPermission == 'both' && ($leadContact->added_by == user()->id || $leadContact->assigned_to == user()->id))
            || user()->id == $leadContact->added_by
            || user()->id == $leadContact->assigned_to)
        );

        // Preserve existing values unless the client explicitly sends a new one.
        $leadContact->client_name = $request->input('client_name', $leadContact->client_name);

        if ($request->exists('client_email')) {
            $leadContact->client_email = $request->input('client_email');
        }

        if ($request->exists('note')) {
            $leadContact->note = trim_editor((string) $request->input('note'));
        }

        if ($request->exists('source_id')) {
            $leadContact->source_id = $this->normalizeNullableInteger($request->input('source_id'));
        }

        if ($request->exists('status_id')) {
            $leadContact->status_id = $this->normalizeNullableInteger($request->input('status_id'));
        }

        if ($request->exists('category_id')) {
            $leadContact->category_id = $this->normalizeNullableInteger($request->input('category_id'));
        }

        if ($request->exists('company_name')) {
            $leadContact->company_name = $request->input('company_name');
        }

        if ($request->exists('website')) {
            $leadContact->website = $request->input('website');
        }

        if ($request->exists('address')) {
            $leadContact->address = $request->input('address');
        }

        if ($request->exists('cell')) {
            $leadContact->cell = $request->input('cell');
        }

        if ($request->exists('office')) {
            $leadContact->office = $request->input('office');
        }

        // city, state, postal_code removed
        if ($request->exists('country')) {
            $leadContact->country = $request->input('country') ?: 'India';
        }

        if ($request->exists('mobile')) {
            $leadContact->mobile = $this->normalizeMobileByCountry(
                $request->input('mobile'),
                $request->input('country', $leadContact->country)
            );
        }

        if ($request->exists('interest_level')) {
            $leadContact->interest_level = $request->input('interest_level');
        }

        if ($request->exists('deal_size')) {
            $leadContact->deal_size = $request->input('deal_size');
        }

        if ($request->exists('contact_status')) {
            $leadContact->contact_status = $request->input('contact_status');
        }

        if ($request->exists('contact_status_reason')) {
            $leadContact->contact_status_reason = $request->input('contact_status_reason');
        }

        if ($request->exists('products_services')) {
            $leadContact->products_services = $request->input('products_services');
        }

        if ($request->exists('added_by')) {
            $leadContact->added_by = $this->normalizeNullableInteger($request->input('added_by'));
        }

        $leadContact->assigned_to = $this->resolvedAssignedTo($request, $leadContact);
        $leadContact->save();

        if ($request->exists('deal_size')) {
            $this->syncConvertedClientDealSize($leadContact);
        }

        $followUpCreated = $this->createLeadFormFollowUp($leadContact, $request);

        // To add custom fields data
        if ($request->custom_fields_data) {
            $leadContact->updateCustomFieldData($request->custom_fields_data);
        }

        // Handle "Save & Add More" action
        if ($request->add_more == 'true') {
            return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('lead-contact.create')]);
        }

        if ($request->input('form_action') === 'schedule_follow_up' && $followUpCreated) {
            return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('lead-contact.show', $leadContact->id) . '?tab=follow-up']);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('lead-contact.index')]);
    }

    public function quickUpdate(QuickUpdateRequest $request, $id)
    {
        $leadContact = Lead::findOrFail($id);
        $this->editPermission = user()->permission('edit_lead');

        abort_403(!$this->canAccessLead($leadContact));

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $leadContact->added_by == user()->id)
            || ($this->editPermission == 'owned' && $leadContact->assigned_to == user()->id)
            || ($this->editPermission == 'both' && ($leadContact->added_by == user()->id || $leadContact->assigned_to == user()->id))
            || user()->id == $leadContact->added_by
            || user()->id == $leadContact->assigned_to)
        );

        $field = (string) $request->field;
        $rawValue = $request->input('value');
        $value = is_string($rawValue) ? trim($rawValue) : $rawValue;

        if (in_array($field, ['source_id', 'category_id', 'status_id', 'assigned_to', 'added_by'], true)) {
            $value = ($value === '' || is_null($value)) ? null : (int) $value;
        }

        if ($field === 'source_id') {
            abort_403(user()->permission('view_lead_sources') === 'none');

            if (!is_null($value)) {
                abort_unless(LeadSource::whereKey($value)->exists(), 422, 'Invalid lead source selected.');
            }
        }

        if ($field === 'category_id') {
            abort_403(user()->permission('view_lead_category') === 'none');

            if (!is_null($value)) {
                abort_unless(LeadCategory::whereKey($value)->exists(), 422, 'Invalid lead category selected.');
            }
        }

        if ($field === 'status_id' && !is_null($value)) {
            abort_unless(LeadStatus::whereKey($value)->exists(), 422, 'Invalid lead status selected.');
        }

        if ($field === 'assigned_to') {
            if (!is_null($value)) {
                $isEmployee = User::allEmployees(null, true, null, company()->id)
                    ->pluck('id')
                    ->contains((int) $value);

                abort_unless($isEmployee, 422, 'Invalid assignee selected.');
            }
        }

        if ($field === 'added_by') {
            abort_403(!$this->canManageLeadAssignment());

            if (!is_null($value)) {
                $isEmployee = User::allEmployees(null, true, null, company()->id)
                    ->pluck('id')
                    ->contains((int) $value);

                abort_unless($isEmployee, 422, 'Invalid employee selected.');
            }
        }

        if ($field === 'client_name') {
            abort_unless(!empty((string) $value), 422, 'Name is required.');
            $value = mb_substr((string) $value, 0, 191);
        }

        if ($field === 'client_email') {
            $value = $value === '' ? null : mb_substr((string) $value, 0, 191);

            if (!is_null($value)) {
                abort_unless(filter_var($value, FILTER_VALIDATE_EMAIL), 422, 'Please provide a valid email address.');

                $emailExists = Lead::where('company_id', company()->id)
                    ->where('client_email', $value)
                    ->where('id', '!=', $leadContact->id)
                    ->exists();

                abort_unless(!$emailExists, 422, 'This email is already in use.');
            }
        }

        if ($field === 'interest_level') {
            $allowed = ['low', 'medium', 'high', 'very_high', ''];
            abort_unless(in_array((string) $value, $allowed, true), 422, 'Invalid interest level.');
            $value = $value === '' ? null : $value;
        }

        if ($field === 'deal_size') {
            if ($value === '' || is_null($value)) {
                $value = null;
            } else {
                abort_unless(is_numeric($value), 422, 'Deal size must be numeric.');
                $value = (float) $value;
                abort_unless($value >= 0, 422, 'Deal size cannot be negative.');
            }
        }

        if ($field === 'contact_status') {
            $allowed = ['pending', 'connected', 'not_connected', ''];
            abort_unless(in_array((string) $value, $allowed, true), 422, 'Invalid contact status.');
            $value = $value === '' ? null : $value;
        }

        if (in_array($field, ['company_name', 'website', 'office', 'country'], true)) {
            $value = $value === '' ? null : mb_substr((string) $value, 0, 191);
        }

        if ($field === 'products_services') {
            $items = [];

            if (is_array($rawValue)) {
                $items = $rawValue;
            } elseif (!is_null($rawValue) && $rawValue !== '') {
                $items = explode(',', (string) $rawValue);
            }

            $items = collect($items)
                ->map(fn ($item) => trim((string) $item))
                ->filter()
                ->unique()
                ->values();

            $value = $items->isEmpty()
                ? null
                : mb_substr($items->implode(', '), 0, 5000);
        }

        if ($field === 'mobile') {
            $countryForMobile = $request->input('country', $leadContact->country);
            $value = $this->normalizeMobileByCountry($value, $countryForMobile);
        }

        if ($field === 'address') {
            $value = $value === '' ? null : mb_substr((string) $value, 0, 5000);
        }

        $currentValue = $leadContact->{$field};
        if ((is_null($currentValue) ? '' : (string) $currentValue) === (is_null($value) ? '' : (string) $value)) {
            $leadContact->loadMissing(['leadSource', 'category', 'leadStatus', 'assignedTo', 'addedBy']);

            return Reply::successWithData(__('messages.updateSuccess'), [
                'field' => $field,
                'value' => $value,
                'source' => $leadContact->leadSource?->type,
                'category' => $leadContact->category?->category_name,
                'lead_status' => $leadContact->leadStatus?->type,
                'statusColor' => $leadContact->leadStatus?->label_color,
                'added_by_name' => $leadContact->addedBy?->name,
                'assigned_to_name' => $leadContact->assignedTo?->name,
            ]);
        }

        $leadContact->{$field} = $value;
        $leadContact->save();

        if ($field === 'deal_size') {
            $this->syncConvertedClientDealSize($leadContact);
        }

        $leadContact->loadMissing(['leadSource', 'category', 'leadStatus', 'assignedTo', 'addedBy']);

        return Reply::successWithData(__('messages.updateSuccess'), [
            'field' => $field,
            'value' => $value,
            'source' => $leadContact->leadSource?->type,
            'category' => $leadContact->category?->category_name,
            'lead_status' => $leadContact->leadStatus?->type,
            'statusColor' => $leadContact->leadStatus?->label_color,
            'added_by_name' => $leadContact->addedBy?->name,
            'assigned_to_name' => $leadContact->assignedTo?->name,
        ]);
    }

    public function quickAddForm(string $type)
    {
        $this->authorizeQuickAdd($type);
        $this->quickAddType = $type;

        return view('lead-contact.quick-add-option-modal', $this->data);
    }

    public function quickAddStore(Request $request, string $type)
    {
        $this->authorizeQuickAdd($type);

        $rules = [
            'name' => ['required', 'string', 'max:191'],
        ];

        if ($type === 'status') {
            $rules['name'][] = 'unique:lead_status,type';
            $rules['label_color'] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        if ($type === 'product') {
            $rules['price'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        if ($type === 'source') {
            $option = new LeadSource();
            $option->type = $validated['name'];
            $field = 'source_id';
            $value = null;
        } elseif ($type === 'category') {
            $option = new LeadCategory();
            $option->category_name = $validated['name'];
            $field = 'category_id';
            $value = null;
        } elseif ($type === 'status') {
            $option = new LeadStatus();
            $option->type = $validated['name'];
            $option->label_color = $validated['label_color'];
            $option->priority = ((int) LeadStatus::max('priority')) + 1;
            $option->default = false;
            $field = 'status_id';
            $value = null;
        } else {
            $option = new Product();
            $option->name = $validated['name'];
            $option->price = $validated['price'];
            $field = 'products_services';
            $value = $validated['name'];
        }

        $option->save();

        return Reply::successWithData(__('messages.recordSaved'), [
            'field' => $field,
            'option' => [
                'value' => $value ?? $option->id,
                'label' => $validated['name'],
            ],
        ]);
    }

    private function authorizeQuickAdd(string $type): void
    {
        abort_unless(in_array($type, ['source', 'category', 'status', 'product'], true), 404);

        if ($type === 'source') {
            abort_403(!in_array(user()->permission('add_lead_sources'), ['all', 'added'], true));
        } elseif ($type === 'category') {
            abort_403(!in_array(user()->permission('add_lead_category'), ['all', 'added'], true));
        } elseif ($type === 'status') {
            abort_403(!in_array('admin', user_roles(), true));
        } else {
            abort_403(!in_array('products', user_modules(), true)
                || !in_array(user()->permission('add_product'), ['all', 'added'], true));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $leadContact = Lead::findOrFail($id);
        $this->deletePermission = user()->permission('delete_lead');

        abort_403(!$this->canAccessLead($leadContact));

        abort_403(!($this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $leadContact->added_by == user()->id)
            || ($this->deletePermission == 'owned' && $leadContact->assigned_to == user()->id)
            || ($this->deletePermission == 'both' && ($leadContact->added_by == user()->id || $leadContact->assigned_to == user()->id))
            || user()->id == $leadContact->added_by
            || user()->id == $leadContact->assigned_to)
        );

        Lead::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));

    }

    public function applyQuickAction(Request $request)
    {
        $actionType = (string) $request->input('action_type');
        $leadIds = collect(explode(',', (string) $request->row_ids))
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter()
            ->unique()
            ->values();

        if ($leadIds->isEmpty()) {
            return Reply::error(__('messages.selectAction'));
        }

        if ($actionType === 'delete') {
            $query = Lead::whereIn('id', $leadIds->all());

            if (!$this->isAdminUser()) {
                $query->where(function ($builder) {
                    $builder->where('added_by', user()->id)
                        ->orWhere('assigned_to', user()->id);
                });
            }

            $deletedCount = $query->delete();

            return Reply::successWithData(__('messages.deleteSuccess'), [
                'deleted_count' => $deletedCount,
            ]);
        }

        if ($actionType === 'assign-to') {
            abort_403(!$this->canManageLeadAssignment());

            $request->validate([
                'assigned_to' => 'required|exists:users,id',
            ]);

            $assignedTo = (int) $request->input('assigned_to');
            $employeeIds = User::allEmployees(null, true, null, company()->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!in_array($assignedTo, $employeeIds, true)) {
                return Reply::error('Invalid assignee selected.');
            }

            $currentUserId = user()->id ?? null;
            $now = now();

            $result = DB::transaction(function () use ($leadIds, $assignedTo, $currentUserId, $now) {
                $leadRows = Lead::query()
                    ->select('id', 'company_id', 'added_by', 'assigned_to')
                    ->whereIn('id', $leadIds->all())
                    ->get();

                $accessibleLeads = $leadRows->filter(function (Lead $lead) {
                    return $this->canAccessLead($lead);
                })->values();

                $eligibleLeads = $accessibleLeads->reject(function (Lead $lead) use ($assignedTo) {
                    return (int) $lead->assigned_to === $assignedTo;
                })->values();

                $skippedCount = $leadRows->count() - $accessibleLeads->count();

                if ($eligibleLeads->isEmpty()) {
                    return null;
                }

                $historyRows = [];
                $historyUserIds = $eligibleLeads
                    ->pluck('assigned_to')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->push($assignedTo)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $userNameMap = User::withoutGlobalScopes()
                    ->whereIn('id', $historyUserIds)
                    ->pluck('name', 'id');

                $newAssigneeName = $userNameMap->get($assignedTo) ?: '--';

                foreach ($eligibleLeads as $lead) {
                    $oldAssigneeId = $lead->assigned_to ? (int) $lead->assigned_to : null;
                    $oldAssigneeName = $oldAssigneeId ? ($userNameMap->get($oldAssigneeId) ?: '--') : '--';

                    $historyRows[] = [
                        'company_id' => $lead->company_id ?: (company()->id ?? null),
                        'lead_id' => $lead->id,
                        'event_type' => 'lead_field_updated',
                        'title' => 'Lead Updated',
                        'description' => 'Assigned To changed from "' . $oldAssigneeName . '" to "' . $newAssigneeName . '".',
                        'field_key' => 'assigned_to',
                        'old_value' => $oldAssigneeName,
                        'new_value' => $newAssigneeName,
                        'meta' => json_encode([
                            'field' => 'assigned_to',
                            'old' => $oldAssigneeName,
                            'new' => $newAssigneeName,
                        ]),
                        'created_by' => $currentUserId,
                        'event_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Keep the write path to one update query and recreate the history rows
                // that the Lead observer would normally write for each saved model.
                Lead::whereIn('id', $eligibleLeads->pluck('id')->all())
                    ->update([
                        'assigned_to' => $assignedTo,
                        'last_updated_by' => $currentUserId,
                        'updated_at' => $now,
                    ]);

                if (!empty($historyRows) && Schema::hasTable('lead_histories')) {
                    DB::table('lead_histories')->insert($historyRows);
                }

                return [
                    'updated_count' => $eligibleLeads->count(),
                    'skipped_count' => $skippedCount,
                ];
            });

            if ($result === null) {
                return Reply::error('No selected leads could be assigned.');
            }

            return Reply::successWithData('Selected leads assigned successfully.', $result);
        }

        return Reply::error(__('messages.selectAction'));
    }

    public function followUpCreate($leadId)
    {
        $this->addFollowUpPermission = user()->permission('add_lead_follow_up');
        abort_403(!in_array($this->addFollowUpPermission, ['all', 'added']));

        $this->leadContact = Lead::findOrFail($leadId);
        abort_403(!$this->canAccessLead($this->leadContact));
        $this->leadId = $leadId;

        return view('lead-contact.followups.create', $this->data);
    }

    public function followUpStore(Request $request)
    {
        $this->addFollowUpPermission = user()->permission('add_lead_follow_up');
        abort_403(!in_array($this->addFollowUpPermission, ['all', 'added']));

        $lead = Lead::findOrFail($request->lead_id);
        abort_403(!$this->canAccessLead($lead));

        $startTime = $this->normalizeCompanyTimeValue($request->start_time);
        $request->merge(['start_time' => $startTime]);

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'next_follow_up_date' => 'required|date_format:"' . company()->date_format . '"',
            'start_time' => 'required|date_format:"' . company()->time_format . '"',
            'remind_time' => 'nullable|required_if:send_reminder,yes|integer|min:1',
            'remind_type' => 'nullable|in:minute,hour,day',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|max:5120',
        ]);

        $followUp = new LeadFollowUp();
        $followUp->lead_id = $lead->id;
        $followUp->remark = trim_editor($request->remark);
        $followUp->next_follow_up_date = Carbon::createFromFormat(
            company()->date_format . ' ' . company()->time_format,
            $request->next_follow_up_date . ' ' . $startTime,
            company()->timezone
        )->setTimezone('UTC');
        $followUp->send_reminder = $request->send_reminder === 'yes' ? 'yes' : 'no';
        $followUp->remind_time = $request->send_reminder === 'yes' ? $request->remind_time : null;
        $followUp->remind_type = $request->send_reminder === 'yes' ? $request->remind_type : null;
        $followUp->status = 'pending';
        $followUp->latitude = null;
        $followUp->longitude = null;
        $followUp->added_by = user()->id;
        $followUp->last_updated_by = user()->id;
        $followUp->save();

        $attachmentCount = $this->storeFollowUpAttachments($followUp, $request);

        $this->syncLeadFollowUpFlag($lead->id);

        $this->pushLeadHistory($lead->id, 'followup_created', [
            'title' => 'Follow-up Added',
            'description' => trim(strip_tags((string) $followUp->remark)) ?: 'New follow-up created.'
                . ($attachmentCount > 0 ? ' | ' . $attachmentCount . ' photo(s) attached' : ''),
            'meta' => [
                'followup_id' => $followUp->id,
                'followup_status' => $followUp->status ?: 'pending',
                'attachment_count' => $attachmentCount,
            ],
        ]);

        return Reply::success(__('messages.recordSaved'));
    }

    public function editFollow($id)
    {
        $this->editFollowUpPermission = user()->permission('edit_lead_follow_up');
        $followUpQuery = LeadFollowUp::with('lead');

        if (Schema::hasTable('lead_follow_up_attachments')) {
            $followUpQuery->with('attachments');
            $this->followAttachments = collect();
        }
        else {
            $this->followAttachments = collect();
        }

        $this->follow = $followUpQuery->findOrFail($id);
        if (Schema::hasTable('lead_follow_up_attachments')) {
            $this->followAttachments = $this->follow->attachments ?? collect();
        }

        abort_403(!$this->canEditFollowUpRecord($this->follow));

        return view('lead-contact.followups.edit', $this->data);
    }

    public function updateFollow(Request $request)
    {
        $this->editFollowUpPermission = user()->permission('edit_lead_follow_up');
        $followUp = LeadFollowUp::with('lead')->findOrFail($request->id);

        $startTime = $this->normalizeCompanyTimeValue($request->start_time);
        $request->merge(['start_time' => $startTime]);

        abort_403(!$this->canEditFollowUpRecord($followUp));

        $request->validate([
            'next_follow_up_date' => 'required|date_format:"' . company()->date_format . '"',
            'start_time' => 'required|date_format:"' . company()->time_format . '"',
            'remind_time' => 'nullable|required_if:send_reminder,yes|integer|min:1',
            'remind_type' => 'nullable|in:minute,hour,day',
            'status' => 'required|in:pending,canceled,completed',
            'attachments' => 'nullable|array',
            'attachments.*' => 'image|max:5120',
        ]);

        $oldStatus = (string) ($followUp->status ?: 'pending');
        $oldRemark = trim(strip_tags((string) $followUp->remark));

        $followUp->remark = trim_editor($request->remark);
        $followUp->next_follow_up_date = Carbon::createFromFormat(
            company()->date_format . ' ' . company()->time_format,
            $request->next_follow_up_date . ' ' . $startTime,
            company()->timezone
        )->setTimezone('UTC');
        $followUp->send_reminder = $request->send_reminder === 'yes' ? 'yes' : 'no';
        $followUp->remind_time = $request->send_reminder === 'yes' ? $request->remind_time : null;
        $followUp->remind_type = $request->send_reminder === 'yes' ? $request->remind_type : null;
        $followUp->status = $request->status;
        $followUp->latitude = null;
        $followUp->longitude = null;
        $followUp->last_updated_by = user()->id;
        $followUp->save();
        $attachmentCount = $this->storeFollowUpAttachments($followUp, $request);
        $this->syncLeadFollowUpFlag($followUp->lead_id);

        $newStatus = (string) ($followUp->status ?: 'pending');
        $newRemark = trim(strip_tags((string) $followUp->remark));

        $changes = [];
        if ($oldStatus !== $newStatus) {
            $changes[] = 'Status: ' . ucfirst($oldStatus) . ' -> ' . ucfirst($newStatus);
        }
        if ($oldRemark !== $newRemark) {
            $changes[] = 'Remark updated';
        }

        $this->pushLeadHistory($followUp->lead_id, 'followup_updated', [
            'title' => 'Follow-up Updated',
            'description' => trim((!empty($changes) ? implode(' | ', $changes) : 'Follow-up details updated.')
                . ($attachmentCount > 0 ? ' | ' . $attachmentCount . ' photo(s) attached' : '')),
            'meta' => [
                'followup_id' => $followUp->id,
                'followup_status' => $followUp->status ?: 'pending',
                'attachment_count' => $attachmentCount,
            ],
        ]);

        return Reply::success(__('messages.updateSuccess'));
    }

    public function deleteFollow($id)
    {
        $this->deleteFollowUpPermission = user()->permission('delete_lead_follow_up');
        $followUp = LeadFollowUp::with('lead')->findOrFail($id);

        abort_403(!$this->canDeleteFollowUpRecord($followUp));

        $leadId = $followUp->lead_id;
        $oldStatus = (string) ($followUp->status ?: 'pending');
        $this->deleteFollowUpAttachments($followUp);
        $followUp->delete();

        $this->syncLeadFollowUpFlag($leadId);

        $this->pushLeadHistory($leadId, 'followup_deleted', [
            'title' => 'Follow-up Deleted',
            'description' => 'A follow-up (' . ucfirst($oldStatus) . ') was deleted.',
        ]);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function changeFollowUpStatus(Request $request)
    {
        $this->editFollowUpPermission = user()->permission('edit_lead_follow_up');
        $followUp = LeadFollowUp::with('lead')->findOrFail($request->id);

        $request->validate([
            'status' => 'required|in:pending,canceled,completed',
        ]);

        abort_403(!$this->canEditFollowUpRecord($followUp));

        $oldStatus = (string) ($followUp->status ?: 'pending');
        $followUp->status = $request->status;
        $followUp->last_updated_by = user()->id;
        $followUp->save();

        $this->syncLeadFollowUpFlag($followUp->lead_id);

        if ($oldStatus !== (string) $followUp->status) {
            $this->pushLeadHistory($followUp->lead_id, 'followup_status_updated', [
                'title' => 'Follow-up Status Changed',
                'description' => 'Status changed from "' . ucfirst($oldStatus) . '" to "' . ucfirst((string) $followUp->status) . '".',
                'meta' => [
                    'followup_id' => $followUp->id,
                    'followup_status' => $followUp->status ?: 'pending',
                ],
            ]);
        }

        return Reply::success(__('messages.leadStatusChangeSuccess'));
    }

    public function convertToClient(Request $request, $id)
    {
        $lead = Lead::findOrFail($id);
        $this->addClientPermission = user()->permission('add_clients');
        abort_403(!$this->canAccessLead($lead));
        abort_403(!in_array($this->addClientPermission, User::ALL_ADDED_BOTH));

        if ($lead->client_id) {
            return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('clients.show', $lead->client_id)]);
        }

        $clientUser = null;

        DB::transaction(function () use ($lead, $request, &$clientUser) {
            $clientUser = null;
            $clientCategoryId = $this->resolveClientCategoryId($lead);

            if (!empty($lead->client_email)) {
                $clientUser = User::withoutGlobalScope(\App\Scopes\ActiveScope::class)
                    ->where('company_id', company()->id)
                    ->where('email', $lead->client_email)
                    ->whereHas('roles', fn($query) => $query->where('name', 'client'))
                    ->first();
            }

            if (!$clientUser) {
                $email = null;

                if (!empty($lead->client_email)) {
                    $emailExists = User::withoutGlobalScope(\App\Scopes\ActiveScope::class)
                        ->where('company_id', company()->id)
                        ->where('email', $lead->client_email)
                        ->exists();
                    $email = $emailExists ? null : $lead->client_email;
                }

                $country = collect(countries())->first(function ($item) use ($lead) {
                    return strtolower($item->nicename) === strtolower((string) $lead->country);
                });

                $clientUser = User::create([
                    'company_id' => company()->id,
                    'name' => $lead->client_name,
                    'email' => $email,
                    'password' => bcrypt(Str::random(24)),
                    'mobile' => $lead->mobile,
                    'country_id' => $country?->id,
                    'country_phonecode' => $country?->phonecode,
                    'locale' => user()->locale ?? 'en',
                    'status' => 'active',
                    'login' => 'disable',
                    'email_notifications' => 0,
                    'added_by' => user()->id,
                ]);

                $role = Role::where('name', 'client')->where('company_id', company()->id)->first()
                    ?? Role::where('name', 'client')->first();

                if ($role) {
                    $clientUser->attachRole($role->id);
                    $clientUser->assignUserRolePermission($role->id);
                }

                $clientUser->clientDetails()->create([
                    'company_id' => company()->id,
                    'company_name' => $lead->company_name,
                    'address' => $lead->address,
                    'office' => $lead->office,
                    'website' => $lead->website,
                    'note' => $lead->note,
                    'category_id' => $clientCategoryId,
                    'added_by' => user()->id,
                    'user_id' => $clientUser->id,
                ]);

                if (!empty(trim(strip_tags((string) $lead->note)))) {
                    ClientNote::create([
                        'title' => 'Lead Conversion Note',
                        'client_id' => $clientUser->id,
                        'details' => trim_editor($lead->note),
                    ]);
                }
            }

            $lead->client_id = $clientUser->id;
            $lead->converted_at = now();

            if ($request->boolean('archive')) {
                $lead->archived_at = now();
            }

            $lead->save();
        });

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('clients.show', $clientUser->id)]);
    }

    /**
     * Lightweight JSON list for mobile/API consumers.
     * Auth: Sanctum token or session (handled by route middleware).
     */
    public function apiIndex(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 20), 100);

        $selectColumns = [
            'id',
            'client_name',
            'client_email',
            'mobile',
            'source_id',
            'status_id',
            'category_id',
            'assigned_to',
            'added_by',
            'note',
            'created_at',
        ];

        // Include next_follow_up only if the column exists to avoid SQL errors on older schemas.
        if (Schema::hasColumn('leads', 'next_follow_up')) {
            $selectColumns[] = 'next_follow_up';
        }

        $query = Lead::query()
            ->with([
                'leadSource' => function ($q) {
                    $q->select('id', DB::raw('type as name'));
                },
                'leadStatus' => function ($q) {
                    $q->select('id', DB::raw('type as name'), 'label_color');
                },
                'category:id,category_name',
            ])
            ->select($selectColumns);

        // Restrict visibility based on existing permissions.
        $viewPermission = user()->permission('view_lead');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if ($viewPermission === 'added') {
            $query->where('added_by', user()->id);
        } elseif ($viewPermission === 'owned') {
            $query->where('assigned_to', user()->id);
        } elseif ($viewPermission === 'both') {
            $query->where(function ($q) {
                $q->where('added_by', user()->id)->orWhere('assigned_to', user()->id);
            });
        }

        // Simple search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate($perPage);

        return response()->json($leads);
    }

    private function syncLeadFollowUpFlag(int $leadId): void
    {
        // Some restored databases may not include this legacy flag column.
        if (!Schema::hasColumn('leads', 'next_follow_up')) {
            return;
        }

        $hasPendingFollowUps = LeadFollowUp::where('lead_id', $leadId)
            ->where('status', 'pending')
            ->exists();

        Lead::whereKey($leadId)->update([
            'next_follow_up' => $hasPendingFollowUps ? 'yes' : 'no',
        ]);
    }

    private function storeFollowUpAttachments(LeadFollowUp $followUp, Request $request): int
    {
        if (!Schema::hasTable('lead_follow_up_attachments') || !$request->hasFile('attachments')) {
            return 0;
        }

        $files = $request->file('attachments');
        if (!is_array($files)) {
            $files = [$files];
        }

        $savedCount = 0;

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $filename = Files::uploadLocalOrS3($file, LeadFollowUpAttachment::FILE_PATH . '/' . $followUp->id);

            LeadFollowUpAttachment::create([
                'lead_id' => $followUp->lead_id,
                'lead_follow_up_id' => $followUp->id,
                'user_id' => user()->id,
                'filename' => $file->getClientOriginalName(),
                'hashname' => $filename,
                'mime_type' => $file->getClientMimeType(),
                'size' => (string) $file->getSize(),
            ]);

            $savedCount++;
        }

        return $savedCount;
    }

    private function deleteFollowUpAttachments(LeadFollowUp $followUp): void
    {
        if (!Schema::hasTable('lead_follow_up_attachments')) {
            return;
        }

        $attachments = $followUp->relationLoaded('attachments')
            ? $followUp->attachments
            : $followUp->attachments()->get();
        $disk = config('filesystems.default');

        foreach ($attachments as $attachment) {
            $path = LeadFollowUpAttachment::FILE_PATH . '/' . $followUp->id . '/' . $attachment->hashname;

            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            LeadFollowUpAttachment::destroy($attachment->id);
        }
    }

    private function syncConvertedClientDealSize(Lead $lead): void
    {
        if (!$lead->client_id || !Schema::hasTable('client_details')) {
            return;
        }

        DB::table('client_details')
            ->where('user_id', $lead->client_id)
            ->where('company_id', company()->id)
            ->update([
                'lead_deal_size' => $lead->deal_size,
                'updated_at' => now(),
            ]);
    }

    private function resolveClientCategoryId(Lead $lead): ?int
    {
        if (!$lead->category_id || !Schema::hasTable('client_categories')) {
            return null;
        }

        $leadCategory = $lead->relationLoaded('category')
            ? $lead->category
            : LeadCategory::query()->select('id', 'category_name')->find($lead->category_id);

        if (!$leadCategory?->category_name) {
            return null;
        }

        return ClientCategory::query()
            ->where('company_id', company()->id)
            ->whereRaw('LOWER(category_name) = ?', [mb_strtolower(trim($leadCategory->category_name))])
            ->value('id');
    }

    private function pushLeadHistory(int $leadId, string $eventType, array $payload = []): void
    {
        if (!Schema::hasTable('lead_histories')) {
            return;
        }

        $lead = Lead::query()->select('id', 'company_id')->find($leadId);
        if (!$lead) {
            return;
        }

        LeadHistory::create([
            'company_id' => $lead->company_id ?: (company()->id ?? null),
            'lead_id' => $lead->id,
            'event_type' => $eventType,
            'title' => $payload['title'] ?? 'Lead Updated',
            'description' => $payload['description'] ?? null,
            'field_key' => $payload['field_key'] ?? null,
            'old_value' => $payload['old_value'] ?? null,
            'new_value' => $payload['new_value'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'created_by' => user()->id ?? null,
            'event_at' => now(),
        ]);
    }

    private function isAdminUser(): bool
    {
        return in_array('admin', user_roles());
    }

    private function canAccessLead(?Lead $lead): bool
    {
        if (!$lead) {
            return false;
        }

        if ($this->isAdminUser()) {
            return true;
        }

        if ((int) $lead->added_by === (int) user()->id) {
            return true;
        }

        return (int) $lead->assigned_to === (int) user()->id;
    }

    private function canEditFollowUpRecord(?LeadFollowUp $followUp): bool
    {
        if (!$followUp) {
            return false;
        }

        $permission = user()->permission('edit_lead_follow_up');

        return $permission === 'all'
            || ($permission === 'added' && (int) $followUp->added_by === (int) user()->id)
            || ($permission === 'owned' && $this->canAccessLead($followUp->lead))
            || ($permission === 'both' && (
                (int) $followUp->added_by === (int) user()->id
                || $this->canAccessLead($followUp->lead)
            ));
    }

    private function canDeleteFollowUpRecord(?LeadFollowUp $followUp): bool
    {
        if (!$followUp) {
            return false;
        }

        $permission = user()->permission('delete_lead_follow_up');

        return $permission === 'all'
            || ($permission === 'added' && (int) $followUp->added_by === (int) user()->id)
            || ($permission === 'owned' && $this->canAccessLead($followUp->lead))
            || ($permission === 'both' && (
                (int) $followUp->added_by === (int) user()->id
                || $this->canAccessLead($followUp->lead)
            ));
    }

    private function canManageLeadAssignment(): bool
    {
        return $this->isAdminUser()
            || user()->permission('add_lead') === 'all'
            || user()->permission('edit_lead') === 'all';
    }

    private function shouldLoadLeadEmployees(): bool
    {
        return user()->permission('add_lead') === 'all' || $this->canManageLeadAssignment();
    }

    private function resolvedAssignedTo(Request $request, ?Lead $lead = null): ?int
    {
        if (!$this->canManageLeadAssignment()) {
            return $lead?->assigned_to;
        }

        if (!$request->exists('assigned_to')) {
            return $lead?->assigned_to;
        }

        return $this->normalizeNullableInteger($request->input('assigned_to'));
    }

    private function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === '' || is_null($value)) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function normalizeMobileByCountry($input, $countryName = null): string
    {
        $countryList = collect(countries());
        $country = $countryList->first(function ($item) use ($countryName) {
            return strtolower((string) $item->nicename) === strtolower((string) $countryName);
        });

        if (!$country) {
            $country = $countryList->first(function ($item) {
                return strtolower((string) $item->nicename) === 'india';
            });
        }

        $countryCode = preg_replace('/\D+/', '', (string) ($country->phonecode ?? '91'));
        abort_unless($countryCode !== '', 422, 'Country phone code is missing.');

        $digits = preg_replace('/\D+/', '', (string) $input);

        if (str_starts_with($digits, $countryCode)) {
            $digits = substr($digits, strlen($countryCode));
        }

        $digits = ltrim($digits, '0');

        if ($countryCode === '91') {
            abort_unless(strlen($digits) === 10, 422, 'For India, mobile number must be 10 digits.');
        } else {
            abort_unless(strlen($digits) >= 6 && strlen($digits) <= 12, 422, 'Mobile number must be between 6 and 12 digits.');
        }

        $fullDigits = $countryCode . $digits;
        abort_unless(strlen($fullDigits) >= 7 && strlen($fullDigits) <= 15, 422, 'Mobile number must be in valid international format.');

        return '+' . $fullDigits;
    }

    private function shouldCreateLeadFormFollowUp(Request $request): bool
    {
        return $request->input('form_action') === 'schedule_follow_up'
            || $request->filled('followup_date')
            || $request->filled('reminder_time')
            || filled(trim(strip_tags((string) $request->followup_note)));
    }

    private function parseLeadFormFollowUpDateTime(Request $request): ?Carbon
    {
        if (!$request->filled('followup_date')) {
            return null;
        }

        $followUpTime = $request->filled('reminder_time')
            ? $this->normalizeCompanyTimeValue($request->reminder_time)
            : now(company()->timezone)->format(company()->time_format);

        return Carbon::createFromFormat(
            company()->date_format . ' ' . company()->time_format,
            $request->followup_date . ' ' . $followUpTime,
            company()->timezone
        )->setTimezone('UTC');
    }

    private function createLeadFormFollowUp(Lead $lead, Request $request): bool
    {
        if (!$this->shouldCreateLeadFormFollowUp($request)) {
            return false;
        }

        $this->addFollowUpPermission = user()->permission('add_lead_follow_up');
        abort_403(!in_array($this->addFollowUpPermission, ['all', 'added']));

        $followUp = new LeadFollowUp();
        $followUp->lead_id = $lead->id;
        $followUp->remark = trim_editor($request->followup_note);
        $followUp->next_follow_up_date = $this->parseLeadFormFollowUpDateTime($request);
        $followUp->send_reminder = 'no';
        $followUp->remind_time = null;
        $followUp->remind_type = null;
        $followUp->status = 'pending';
        $followUp->latitude = $request->latitude;
        $followUp->longitude = $request->longitude;
        $followUp->added_by = user()->id;
        $followUp->last_updated_by = user()->id;
        $followUp->save();

        $this->syncLeadFollowUpFlag($lead->id);

        return true;
    }

    private function normalizeCompanyTimeValue(?string $time): ?string
    {
        if (is_null($time)) {
            return null;
        }

        $time = trim((string) $time);
        $time = preg_replace('/\./', ':', $time);
        $time = preg_replace('/\s+/', ' ', $time);
        $companyTimeFormat = company()->time_format;

        if ($companyTimeFormat === 'h:i a') {
            return strtolower($time);
        }

        if ($companyTimeFormat === 'h:i A') {
            return strtoupper($time);
        }

        return $time;
    }

    public function importLead()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('app.menu.lead');

        $this->addPermission = user()->permission('add_lead');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (request()->ajax()) {
            $html = view('leads.ajax.import', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'leads.ajax.import';

        return view('leads.create', $this->data);
    }

    public function importStore(ImportRequest $request)
    {
        $this->importFileProcess($request, LeadImport::class);

        $view = view('leads.ajax.import_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importJobProcess($request, LeadImport::class, ImportLeadJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    /**
     * Store follow-up note from mobile app (API endpoint).
     * Accepts ISO8601 datetime format instead of company-specific format.
     */
    public function storeFollowUpApi(Request $request, $id)
    {
        $this->addFollowUpPermission = user()->permission('add_lead_follow_up');
        abort_403(!in_array($this->addFollowUpPermission, ['all', 'added']));

        $lead = Lead::findOrFail($id);
        abort_403(!$this->canAccessLead($lead));

        $request->validate([
            'note' => 'required|string',
            'scheduled_at' => 'nullable|date',
            'follow_up_date' => 'nullable|date',
        ]);

        $followUp = new LeadFollowUp();
        $followUp->lead_id = $lead->id;
        $followUp->remark = trim($request->note);

        // Parse scheduled datetime - use either field sent by mobile app
        $scheduledDateTime = $request->scheduled_at ?? $request->follow_up_date;
        if ($scheduledDateTime) {
            $followUp->next_follow_up_date = Carbon::parse($scheduledDateTime, 'UTC')
                ->setTimezone(company()->timezone);
        } else {
            $followUp->next_follow_up_date = null;
        }

        $followUp->send_reminder = 'no';
        $followUp->remind_time = null;
        $followUp->remind_type = null;
        $followUp->status = 'pending';
        $followUp->latitude = $request->latitude;
        $followUp->longitude = $request->longitude;
        $followUp->added_by = user()->id;
        $followUp->last_updated_by = user()->id;
        $followUp->save();

        $this->syncLeadFollowUpFlag($lead->id);

        $this->pushLeadHistory($lead->id, 'followup_created', [
            'title' => 'Follow-up Added',
            'description' => trim(strip_tags((string) $followUp->remark)) ?: 'New follow-up created.',
            'meta' => [
                'followup_id' => $followUp->id,
                'followup_status' => $followUp->status ?: 'pending',
            ],
        ]);

        // Return follow-up in mobile-friendly format
        return response()->json([
            'success' => true,
            'message' => 'Follow-up note saved successfully.',
            'data' => [
                'id' => $followUp->id,
                'lead_id' => $followUp->lead_id,
                'note' => $followUp->remark,
                'created_at' => $followUp->created_at?->toIso8601String(),
                'created_by' => optional($followUp->addedBy)->name ?: user()->name,
            ],
        ], 201);
    }

}
