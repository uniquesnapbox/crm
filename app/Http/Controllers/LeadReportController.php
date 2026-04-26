<?php

namespace App\Http\Controllers;

use App\DataTables\LeadReportDataTable;
use App\Models\Company;
use App\Models\Deal;
use App\Models\LeadAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LeadReportController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Archived Deals Report';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(LeadReportDataTable $dataTable)
    {
        abort_403(!in_array('admin', user_roles()));

        if (!request()->ajax()) {
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);

            $this->agents = Cache::remember('lead_report_agents_' . company()->id, now()->addMinutes(30), function () {
                return LeadAgent::with(['user:id,name,image,salutation,status'])
                    ->join('users', 'users.id', 'lead_agents.user_id')
                    ->where('users.status', 'active')
                    ->selectRaw('MIN(lead_agents.id) as id, lead_agents.user_id')
                    ->groupBy('lead_agents.user_id')
                    ->orderBy('users.name')
                    ->get();
            });
        }

        return $dataTable->render('reports.lead.index', $this->data);
    }

}
