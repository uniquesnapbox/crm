<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\CustomFieldGroup;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketCustomForm;
use Illuminate\Http\Request;

class TicketCustomFormController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'modules.ticketForm';
        $this->middleware(function ($request, $next) {
            if (!in_array('tickets', $this->user->modules)) {
                abort(403);
            }

            return $next($request);
        });
    }

    public function index()
    {
        $this->ticketFormFields = TicketCustomForm::get();
        $this->ticketFormStatus = company()->ticket_form_status ?? 'active';
        $this->manageCustomFieldPermission = user()->permission('manage_custom_field_setting');
        $this->ticketCustomFieldGroupId = CustomFieldGroup::query()
            ->where('company_id', company()->id)
            ->where('model', Ticket::CUSTOM_FIELD_MODEL)
            ->value('id');

        return view('tickets.ticket-form.index', $this->data);
    }

    /**
     * update record
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        TicketCustomForm::where('id', $id)->update([
            'status' => $request->status
        ]);

        return Reply::dataOnly([]);
    }

    public function updateFormStatus(Request $request)
    {
        $status = $request->status === 'inactive' ? 'inactive' : 'active';

        $company = Company::findOrFail(company()->id);
        $company->ticket_form_status = $status;
        $company->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    /**
     * sort fields order
     *
     * @return \Illuminate\Http\Response
     */
    public function sortFields()
    {
        $sortedValues = request('sortedValues');

        foreach ($sortedValues as $key => $value) {
            TicketCustomForm::where('id', $value)->update(['field_order' => $key + 1]);
        }

        return Reply::dataOnly([]);
    }

}
