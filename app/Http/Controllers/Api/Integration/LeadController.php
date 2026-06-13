<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integration\LeadResource;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    private function leadsQuery(Request $request): Builder
    {
        $query = Lead::query()
            ->with([
                'leadSource:id,type',
                'category:id,category_name',
                'addedBy:id,name,email',
                'assignedTo:id,name,email',
                'latestFollowUp' => function ($query) {
                    $query->select([
                        'lead_follow_up.id',
                        'lead_follow_up.lead_id',
                        'lead_follow_up.remark',
                        'lead_follow_up.next_follow_up_date',
                        'lead_follow_up.status',
                        'lead_follow_up.latitude',
                        'lead_follow_up.longitude',
                    ]);
                },
            ])
            ->whereNull('archived_at')
            ->orderByDesc('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $like = '%' . $search . '%';

            $query->where(function (Builder $builder) use ($like) {
                $builder->where('client_name', 'like', $like)
                    ->orWhere('client_email', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->orWhere('mobile', 'like', $like);
            });
        }

        if ($statusId = (int) $request->query('status_id', 0)) {
            $query->where('status_id', $statusId);
        }

        if ($assignedTo = (int) $request->query('assigned_to', 0)) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($addedBy = (int) $request->query('added_by', 0)) {
            $query->where('added_by', $addedBy);
        }

        if ($converted = $request->query('converted')) {
            if ($converted === 'yes') {
                $query->whereNotNull('converted_at');
            }
            elseif ($converted === 'no') {
                $query->whereNull('converted_at');
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $defaultPerPage = max((int) config('integration_api.default_per_page', 12), 1);
        $maxPerPage = max((int) config('integration_api.max_per_page', 50), $defaultPerPage);
        $perPage = min(max((int) $request->integer('per_page', $defaultPerPage), 1), $maxPerPage);

        $leads = $this->leadsQuery($request)
            ->paginate($perPage)
            ->appends($request->query());

        return LeadResource::collection($leads)->additional([
            'success' => true,
            'message' => 'Leads fetched successfully.',
        ]);
    }

    public function show(Request $request, int $leadId)
    {
        $lead = $this->leadsQuery($request)->whereKey($leadId)->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found.',
            ], 404);
        }

        return (new LeadResource($lead))->additional([
            'success' => true,
            'message' => 'Lead fetched successfully.',
        ]);
    }
}
