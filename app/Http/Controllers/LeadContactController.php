<?php

namespace App\Http\Controllers;

use App\DataTables\LeadFollowupDataTable;
use App\DataTables\LeadContactDataTable;
use App\DataTables\LeadNotesDataTable;
use App\Helper\Reply;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Lead\StoreRequest;
use App\Http\Requests\Lead\UpdateRequest;
use App\Imports\LeadImport;
use App\Jobs\ImportLeadJob;
use App\Models\ClientNote;
use App\Models\LeadCategory;
use App\Models\Lead;
use App\Models\LeadCustomForm;
use App\Models\LeadFollowUp;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Traits\ImportExcel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            $this->categories = LeadCategory::get();
            $this->sources = LeadSource::get();
            $this->employees = User::allEmployees();
        }

        return $dataTable->render('lead-contact.index', $this->data);

    }

    public function show($id)
    {
        $this->leadContact = Lead::with(['leadSource', 'category', 'addedBy', 'assignedTo', 'followUps', 'latestFollowUp'])->findOrFail($id)->withCustomFields();

        $this->viewPermission = user()->permission('view_lead');

        abort_403(!($this->viewPermission == 'all' && $this->isAdminUser()) && !$this->canAccessLead($this->leadContact));

        $this->pageTitle = $this->leadContact->client_name; // removed salutation

        $this->categories = LeadCategory::all();

        $this->leadFormFields = LeadCustomForm::with('customField')->where('status', 'active')->where('custom_fields_id', '!=', 'null')->get();

        $this->leadId = $id;

        if ($this->leadContact->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->leadContact->getCustomFieldGroupsWithFields()->fields;
        }

        $this->deleteLeadPermission = user()->permission('delete_lead');

        $tab = request('tab');

        switch ($tab) {
        case 'follow-up':
            return $this->followUps();
        case 'notes':
            return $this->notes();
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
            $this->employees = User::allEmployees();
        }

        $defaultStatus = LeadStatus::where('default', '1')->first();
        $this->columnId = ((request('column_id') != '') ? request('column_id') : $defaultStatus->id);

        $leadContact = new Lead();

        if ($leadContact->getCustomFieldGroupsWithFields()) {
            $this->fields = $leadContact->getCustomFieldGroupsWithFields()->fields;
        }

        $this->products = Product::all();
        $this->sources = LeadSource::all();
        $this->status = LeadStatus::all();
        $this->categories = LeadCategory::all();
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
        $leadContact->note = trim_editor($request->note);
        $leadContact->source_id = $request->source_id;
        $leadContact->client_id = $existingUser?->id;
        $leadContact->company_name = $request->company_name;
        $leadContact->website = $request->website;
        $leadContact->address = $request->address;
        $leadContact->cell = $request->cell;
        $leadContact->office = $request->office;
        // city, state, postal_code removed
        $leadContact->country = $request->country;
        $leadContact->mobile = $request->mobile;
        $leadContact->added_by = $request->added_by ?? user()->id; // save added_by, fallback to current user
        $leadContact->assigned_to = $this->resolvedAssignedTo($request);
        $leadContact->save();

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

        if ($redirectUrl == '') {
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
        $this->leadContact = Lead::with('leadSource', 'category', 'assignedTo')->findOrFail($id)->withCustomFields();

        $this->editPermission = user()->permission('edit_lead');

        abort_403(!$this->canAccessLead($this->leadContact));

        abort_403(!($this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->leadContact->added_by == user()->id)
            || ($this->editPermission == 'owned' && $this->leadContact->assigned_to == user()->id)
            || ($this->editPermission == 'both' && ($this->leadContact->added_by == user()->id || $this->leadContact->assigned_to == user()->id))
            || user()->id == $this->leadContact->added_by)
        );

        if ($this->shouldLoadLeadEmployees()) {
            $this->employees = User::allEmployees();
        }

        if ($this->leadContact->getCustomFieldGroupsWithFields()) {
            $this->fields = $this->leadContact->getCustomFieldGroupsWithFields()->fields;
        }

        $this->sources = LeadSource::all();
        $this->categories = LeadCategory::all();
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

        // salutation removed
        $leadContact->client_name = $request->client_name;
        $leadContact->client_email = $request->client_email;
        $leadContact->note = trim_editor($request->note);
        $leadContact->source_id = $request->source_id;
        $leadContact->category_id = $request->category_id;
        $leadContact->company_name = $request->company_name;
        $leadContact->website = $request->website;
        $leadContact->address = $request->address;
        $leadContact->cell = $request->cell;
        $leadContact->office = $request->office;
        // city, state, postal_code removed
        $leadContact->country = $request->country;
        $leadContact->mobile = $request->mobile;
        $leadContact->added_by = $request->added_by ?? $leadContact->added_by; // update added_by if provided
        $leadContact->assigned_to = $this->resolvedAssignedTo($request, $leadContact);
        $leadContact->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $leadContact->updateCustomFieldData($request->custom_fields_data);
        }

        // Handle "Save & Add More" action
        if ($request->add_more == 'true') {
            return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('lead-contact.create')]);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('lead-contact.index')]);
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
        $leadIds = array_filter(explode(',', $request->row_ids));
        $query = Lead::whereIn('id', $leadIds);

        if (!$this->isAdminUser()) {
            $query->where(function ($builder) {
                $builder->where('added_by', user()->id)
                    ->orWhere('assigned_to', user()->id);
            });
        }

        $query->delete();

        return Reply::success(__('messages.deleteSuccess'));
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

        $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'next_follow_up_date' => 'required|date_format:"' . company()->date_format . '"',
            'start_time' => 'required',
            'remind_time' => 'nullable|required_if:send_reminder,yes|integer|min:1',
            'remind_type' => 'nullable|in:minute,hour,day',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $followUp = new LeadFollowUp();
        $followUp->lead_id = $lead->id;
        $followUp->remark = trim_editor($request->remark);
        $followUp->next_follow_up_date = Carbon::createFromFormat(
            company()->date_format . ' ' . company()->time_format,
            $request->next_follow_up_date . ' ' . $request->start_time,
            company()->timezone
        )->setTimezone('UTC');
        $followUp->send_reminder = $request->send_reminder === 'yes' ? 'yes' : 'no';
        $followUp->remind_time = $request->send_reminder === 'yes' ? $request->remind_time : null;
        $followUp->remind_type = $request->send_reminder === 'yes' ? $request->remind_type : null;
        $followUp->status = 'pending';
        $followUp->latitude = $request->latitude;
        $followUp->longitude = $request->longitude;
        $followUp->added_by = user()->id;
        $followUp->last_updated_by = user()->id;
        $followUp->save();

        $lead->next_follow_up = 'yes';
        $lead->save();

        return Reply::success(__('messages.recordSaved'));
    }

    public function editFollow($id)
    {
        $this->editFollowUpPermission = user()->permission('edit_lead_follow_up');
        $this->follow = LeadFollowUp::with('lead')->findOrFail($id);
        abort_403(!$this->canAccessLead($this->follow->lead));

        abort_403(!($this->editFollowUpPermission == 'all'
            || ($this->editFollowUpPermission == 'added' && $this->follow->added_by == user()->id)
            || ($this->editFollowUpPermission == 'owned' && $this->canAccessLead($this->follow->lead))
            || ($this->editFollowUpPermission == 'both' && ($this->follow->added_by == user()->id || $this->canAccessLead($this->follow->lead)))
        ));

        return view('lead-contact.followups.edit', $this->data);
    }

    public function updateFollow(Request $request)
    {
        $this->editFollowUpPermission = user()->permission('edit_lead_follow_up');
        $followUp = LeadFollowUp::with('lead')->findOrFail($request->id);
        abort_403(!$this->canAccessLead($followUp->lead));

        abort_403(!($this->editFollowUpPermission == 'all'
            || ($this->editFollowUpPermission == 'added' && $followUp->added_by == user()->id)
            || ($this->editFollowUpPermission == 'owned' && $this->canAccessLead($followUp->lead))
            || ($this->editFollowUpPermission == 'both' && ($followUp->added_by == user()->id || $this->canAccessLead($followUp->lead)))
        ));

        $request->validate([
            'next_follow_up_date' => 'required|date_format:"' . company()->date_format . '"',
            'start_time' => 'required',
            'remind_time' => 'nullable|required_if:send_reminder,yes|integer|min:1',
            'remind_type' => 'nullable|in:minute,hour,day',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'required|in:pending,canceled,completed',
        ]);

        $followUp->remark = trim_editor($request->remark);
        $followUp->next_follow_up_date = Carbon::createFromFormat(
            company()->date_format . ' ' . company()->time_format,
            $request->next_follow_up_date . ' ' . $request->start_time,
            company()->timezone
        )->setTimezone('UTC');
        $followUp->send_reminder = $request->send_reminder === 'yes' ? 'yes' : 'no';
        $followUp->remind_time = $request->send_reminder === 'yes' ? $request->remind_time : null;
        $followUp->remind_type = $request->send_reminder === 'yes' ? $request->remind_type : null;
        $followUp->status = $request->status;
        $followUp->latitude = $request->latitude;
        $followUp->longitude = $request->longitude;
        $followUp->last_updated_by = user()->id;
        $followUp->save();
        $this->syncLeadFollowUpFlag($followUp->lead_id);

        return Reply::success(__('messages.updateSuccess'));
    }

    public function deleteFollow($id)
    {
        $this->deleteFollowUpPermission = user()->permission('delete_lead_follow_up');
        $followUp = LeadFollowUp::with('lead')->findOrFail($id);
        abort_403(!$this->canAccessLead($followUp->lead));

        abort_403(!($this->deleteFollowUpPermission == 'all'
            || ($this->deleteFollowUpPermission == 'added' && $followUp->added_by == user()->id)
            || ($this->deleteFollowUpPermission == 'owned' && $this->canAccessLead($followUp->lead))
            || ($this->deleteFollowUpPermission == 'both' && ($followUp->added_by == user()->id || $this->canAccessLead($followUp->lead)))
        ));

        $leadId = $followUp->lead_id;
        $followUp->delete();

        $this->syncLeadFollowUpFlag($leadId);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function changeFollowUpStatus(Request $request)
    {
        $this->editFollowUpPermission = user()->permission('edit_lead_follow_up');
        $followUp = LeadFollowUp::with('lead')->findOrFail($request->id);
        abort_403(!$this->canAccessLead($followUp->lead));

        abort_403(!($this->editFollowUpPermission == 'all'
            || ($this->editFollowUpPermission == 'added' && $followUp->added_by == user()->id)
            || ($this->editFollowUpPermission == 'owned' && $this->canAccessLead($followUp->lead))
            || ($this->editFollowUpPermission == 'both' && ($followUp->added_by == user()->id || $this->canAccessLead($followUp->lead)))
        ));

        $followUp->status = $request->status;
        $followUp->last_updated_by = user()->id;
        $followUp->save();

        $this->syncLeadFollowUpFlag($followUp->lead_id);

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
                    'category_id' => $lead->category_id,
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

        return $request->filled('assigned_to') ? (int) $request->assigned_to : null;
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

}
