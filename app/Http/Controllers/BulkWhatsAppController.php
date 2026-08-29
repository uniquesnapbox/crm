<?php

namespace App\Http\Controllers;

use App\DataTables\LeadContactDataTable;
use App\Jobs\SendBulkWhatsAppRecipientJob;
use App\Models\BulkWhatsAppCampaign;
use App\Models\BulkWhatsAppTemplate;
use App\Models\Lead;
use App\Models\LeadCategory;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\User;
use App\Services\BulkWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BulkWhatsAppController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Bulk WhatsApp';

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leads', $this->user->modules));

            return $next($request);
        });
    }

    public function index(LeadContactDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_lead');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->categories = LeadCategory::query()->select('id', 'category_name')->orderBy('category_name')->get();
            $this->sources = LeadSource::query()->select('id', 'type')->orderBy('type')->get();
            $this->statuses = LeadStatus::query()->select('id', 'type', 'label_color', 'priority', 'default')->orderBy('priority')->get();
            $this->employees = User::allEmployees();
            $this->assignableEmployees = User::allEmployees(null, true, null, company()->id);
            $this->templates = BulkWhatsAppTemplate::query()
                ->select('id', 'name', 'message', 'is_active')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            $this->sessionKey = $this->resolveSessionKey();
            $this->messagePlaceholder = 'Hi {{client_name}}, this is a bulk WhatsApp test message from CRM.';
        }

        return $dataTable->render('whatsapp.bulk.index', $this->data);
    }

    public function preview(Request $request, BulkWhatsAppService $bulkService): JsonResponse
    {
        [$leads, $template, $message] = $this->resolveSelectedCampaignPayload($request);

        if ($leads->isEmpty()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please select at least one lead or contact.',
            ], 422);
        }

        if ($message === '') {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please add a WhatsApp message or choose a template.',
            ], 422);
        }

        $previewRecipients = $bulkService->previewRecipients($leads, $message, $template);
        $summary = $this->buildSummary($previewRecipients);

        return response()->json([
            'status' => 'success',
            'message' => 'Preview generated successfully.',
            'data' => [
                'summary' => $summary,
                'recipients' => $previewRecipients,
            ],
        ]);
    }

    public function send(Request $request, BulkWhatsAppService $bulkService): JsonResponse
    {
        [$leads, $template, $message, $filters] = $this->resolveSelectedCampaignPayload($request, true);

        if ($leads->isEmpty()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please select at least one lead or contact.',
            ], 422);
        }

        if ($message === '') {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please add a WhatsApp message or choose a template.',
            ], 422);
        }

        $campaignName = trim((string) $request->input('campaign_name', ''));
        if ($campaignName === '') {
            $campaignName = 'Bulk WhatsApp ' . now()->format('Y-m-d H:i');
        }

        $campaign = $bulkService->createCampaign($campaignName, $message, $leads, $template, $filters);
        $campaign->forceFill(['status' => 'running'])->saveQuietly();

        foreach ($campaign->recipients()->where('status', 'pending')->orderBy('id')->pluck('id') as $recipientId) {
            SendBulkWhatsAppRecipientJob::dispatchSync((int) $recipientId);
        }

        $progress = $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk WhatsApp campaign processed successfully.',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign, $progress),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    public function status(BulkWhatsAppCampaign $campaign): JsonResponse
    {
        abort_403((int) $campaign->company_id !== (int) company()->id);

        $progress = $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign, $progress),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    public function logs(BulkWhatsAppCampaign $campaign): JsonResponse
    {
        abort_403((int) $campaign->company_id !== (int) company()->id);

        $progress = $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign, $progress),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $template = BulkWhatsAppTemplate::create([
            'company_id' => company()->id,
            'created_by' => user()->id,
            'updated_by' => user()->id,
            'name' => trim($validated['name']),
            'message' => trim($validated['message']),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Template saved successfully.',
            'data' => [
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'message' => $template->message,
                ],
            ],
        ]);
    }

    private function resolveSelectedCampaignPayload(Request $request, bool $includeFilters = false): array
    {
        $leadIds = collect($request->input('lead_ids', []))
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $template = null;
        if ($request->filled('template_id')) {
            $template = BulkWhatsAppTemplate::query()->find((int) $request->input('template_id'));
        }

        $message = trim((string) $request->input('message', ''));
        if ($message === '' && $template) {
            $message = trim((string) $template->message);
        }

        $leads = $this->selectedLeads($leadIds);

        if ($includeFilters) {
            return [$leads, $template, $message, $this->selectedFilters($request)];
        }

        return [$leads, $template, $message];
    }

    private function selectedLeads(Collection $leadIds): Collection
    {
        if ($leadIds->isEmpty()) {
            return collect();
        }

        $leads = Lead::query()
            ->with([
                'category:id,category_name',
                'leadSource:id,type',
                'leadStatus:id,type',
                'addedBy' => fn ($query) => $query->without(['clientDetails', 'employeeDetail', 'leaves', 'roles'])->select('id', 'name', 'company_id'),
                'assignedTo' => fn ($query) => $query->without(['clientDetails', 'employeeDetail', 'leaves', 'roles'])->select('id', 'name', 'company_id'),
            ])
            ->whereIn('id', $leadIds->all())
            ->whereNull('archived_at')
            ->orderBy('client_name')
            ->get();

        return $leads->filter(fn (Lead $lead) => $this->canAccessLead($lead))->values();
    }

    private function selectedFilters(Request $request): array
    {
        return [
            'type' => $request->input('type'),
            'category_id' => $request->input('category_id'),
            'source_id' => $request->input('source_id'),
            'status_id' => $request->input('status_id'),
            'interest_level' => $request->input('interest_level'),
            'filter_addedBy' => $request->input('filter_addedBy'),
            'filter_assignedTo' => $request->input('filter_assignedTo'),
            'date_filter_on' => $request->input('date_filter_on'),
            'startDate' => $request->input('startDate'),
            'endDate' => $request->input('endDate'),
        ];
    }

    private function buildSummary(array $previewRecipients): array
    {
        $total = count($previewRecipients);
        $ready = collect($previewRecipients)->where('status', 'ready')->count();
        $missingPhone = collect($previewRecipients)->where('status', 'missing_phone')->count();

        return [
            'total' => $total,
            'ready' => $ready,
            'missing_phone' => $missingPhone,
        ];
    }

    private function campaignSummary(BulkWhatsAppCampaign $campaign, ?array $progress = null): array
    {
        $progress ??= $campaign->refreshProgress();

        return [
            'total' => (int) $campaign->recipient_count,
            'sent' => (int) $campaign->sent_count,
            'failed' => (int) $campaign->failed_count,
            'status' => $campaign->status,
            'progress' => (int) ($progress['progress'] ?? 0),
        ];
    }

    private function campaignPayload(BulkWhatsAppCampaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'session_key' => $campaign->session_key,
            'recipient_count' => (int) $campaign->recipient_count,
            'sent_count' => (int) $campaign->sent_count,
            'failed_count' => (int) $campaign->failed_count,
            'started_at' => optional($campaign->started_at)->toDateTimeString(),
            'completed_at' => optional($campaign->completed_at)->toDateTimeString(),
            'last_error' => $campaign->last_error,
        ];
    }

    private function campaignLogsPayload(BulkWhatsAppCampaign $campaign): array
    {
        return $campaign->recipients
            ->sortByDesc('id')
            ->values()
            ->map(function ($recipient) {
                return [
                    'id' => $recipient->id,
                    'lead_id' => $recipient->lead_id,
                    'lead_name' => $recipient->lead_name,
                    'phone' => $recipient->phone,
                    'status' => $recipient->status,
                    'error_message' => $recipient->error_message,
                    'provider_message_id' => $recipient->provider_message_id,
                    'sent_at' => optional($recipient->sent_at)->toDateTimeString(),
                ];
            })
            ->all();
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

    private function resolveSessionKey(): string
    {
        $setting = \App\Models\WhatsappNotificationSetting::withoutGlobalScopes()
            ->where('company_id', company()->id)
            ->first();

        return $setting?->resolved_whatsapp_session_key ?: preg_replace('/\D+/', '', (string) config('services.whatsapp_service.session', ''));
    }
}
