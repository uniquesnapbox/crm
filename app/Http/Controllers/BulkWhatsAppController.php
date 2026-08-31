<?php

namespace App\Http\Controllers;

use App\DataTables\LeadContactDataTable;
use App\Jobs\ProcessBulkWhatsAppCampaignJob;
use App\Models\BulkWhatsAppCampaign;
use App\Models\BulkWhatsAppTemplate;
use App\Models\Lead;
use App\Models\LeadCategory;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Product;
use App\Models\User;
use App\Services\BulkWhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $this->authorizeBulkWhatsAppView();

        if (!request()->ajax()) {
            $this->categories = LeadCategory::query()->select('id', 'category_name')->orderBy('category_name')->get();
            $this->sources = LeadSource::query()->select('id', 'type')->orderBy('type')->get();
            $this->statuses = LeadStatus::query()->select('id', 'type', 'label_color', 'priority', 'default')->orderBy('priority')->get();
            $this->products = Product::query()->select('id', 'name')->orderBy('name')->get();
            $this->employees = User::allEmployees();
            $this->assignableEmployees = User::allEmployees(null, true, null, company()->id);
            $this->templates = BulkWhatsAppTemplate::query()
                ->select('id', 'name', 'message', 'attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size', 'is_active')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            $this->sessionKey = $this->resolveSessionKey();
            $this->messagePlaceholder = 'Hi {{client_name}}, this is a bulk WhatsApp test message from CRM.';
        }

        return $dataTable->render('whatsapp.bulk.index', $this->data);
    }

    public function history()
    {
        $this->authorizeBulkWhatsAppView();

        $this->pageTitle = 'Campaign History';
        $this->campaigns = BulkWhatsAppCampaign::query()
            ->with('creator:id,name')
            ->where('company_id', company()->id)
            ->latest('started_at')
            ->latest('id')
            ->paginate(15);

        // Recalculate counts from recipient logs so history remains accurate after queue retries.
        $this->campaigns->getCollection()->each(function (BulkWhatsAppCampaign $campaign) {
            $campaign->setAttribute('report_progress', $campaign->refreshProgress());
        });

        return view('whatsapp.bulk.history', $this->data);
    }

    public function reports(Request $request)
    {
        $this->authorizeBulkWhatsAppView();

        $this->pageTitle = 'Campaign Reports';
        $this->campaignOptions = BulkWhatsAppCampaign::query()
            ->where('company_id', company()->id)
            ->select('id', 'name', 'started_at', 'created_at', 'status')
            ->latest('started_at')
            ->latest('id')
            ->limit(500)
            ->get();
        $this->campaign = null;
        $this->summary = null;
        $this->recipients = null;

        $campaignId = (int) $request->input('campaign', 0);
        if ($campaignId > 0) {
            $this->campaign = BulkWhatsAppCampaign::query()
                ->with('creator:id,name')
                ->where('company_id', company()->id)
                ->findOrFail($campaignId);

            $progress = $this->campaign->refreshProgress();
            $this->summary = $this->campaignSummary($this->campaign, $progress);
            $this->recipients = $this->campaign->recipients()
                ->with('lead:id,client_name')
                ->latest('id')
                ->paginate(25)
                ->withQueryString();
        }

        return view('whatsapp.bulk.reports', $this->data);
    }

    public function filteredLeadIds(LeadContactDataTable $dataTable): JsonResponse
    {
        $this->authorizeBulkWhatsAppView();

        $leadIds = $dataTable->query(new Lead())
            ->pluck('leads.id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'lead_ids' => $leadIds,
                'count' => $leadIds->count(),
            ],
        ]);
    }

    public function preview(Request $request, BulkWhatsAppService $bulkService): JsonResponse
    {
        $this->authorizeBulkWhatsAppView();

        [$leads, $template, $message, $filters, $attachment, $hasAttachment] = $this->resolveSelectedCampaignPayload($request, $bulkService);

        if ($leads->isEmpty()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please select at least one lead or contact.',
            ], 422);
        }

        if ($message === '' && !$hasAttachment) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please add a WhatsApp message or attach an image before previewing.',
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
                'attachment' => $this->attachmentPayload($attachment),
                'filters' => $filters,
            ],
        ]);
    }

    public function send(Request $request, BulkWhatsAppService $bulkService): JsonResponse
    {
        $this->authorizeBulkWhatsAppSend();

        [$leads, $template, $message, $filters, $attachment, $hasAttachment, $delayMinSeconds, $delayMaxSeconds] = $this->resolveSelectedCampaignPayload($request, $bulkService, true);

        if ($leads->isEmpty()) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please select at least one lead or contact.',
            ], 422);
        }

        if ($message === '' && !$hasAttachment) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Please add a WhatsApp message or attach an image before sending.',
            ], 422);
        }

        $campaignName = trim((string) $request->input('campaign_name', ''));
        if ($campaignName === '') {
            $campaignName = 'Bulk WhatsApp ' . now()->format('Y-m-d H:i');
        }

        $campaign = $bulkService->createCampaign(
            $campaignName,
            $message,
            $leads,
            $template,
            $filters,
            $attachment,
            $delayMinSeconds,
            $delayMaxSeconds
        );

        $this->dispatchCampaignProcessor($campaign);

        $progress = $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'message' => 'Bulk WhatsApp campaign queued successfully.',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign, $progress),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    public function status(BulkWhatsAppCampaign $campaign): JsonResponse
    {
        $this->authorizeBulkWhatsAppView();
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
        $this->authorizeBulkWhatsAppView();
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

    public function storeTemplate(Request $request, BulkWhatsAppService $bulkService): JsonResponse
    {
        $this->authorizeBulkWhatsAppSend();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'message' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $attachmentMeta = null;
        if ($request->hasFile('attachment')) {
            $attachmentMeta = $bulkService->storeUploadedAttachment(
                $request->file('attachment'),
                'bulk-whatsapp/templates/' . company()->id
            );
        }

        if (blank($validated['message'] ?? '') && !$attachmentMeta) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Template message or image attachment is required.',
            ], 422);
        }

        $template = BulkWhatsAppTemplate::create([
            'company_id' => company()->id,
            'created_by' => user()->id,
            'updated_by' => user()->id,
            'name' => trim($validated['name']),
            'message' => trim((string) ($validated['message'] ?? '')),
            'attachment_path' => $attachmentMeta['path'] ?? null,
            'attachment_name' => $attachmentMeta['name'] ?? null,
            'attachment_mime' => $attachmentMeta['mime'] ?? null,
            'attachment_size' => $attachmentMeta['size'] ?? null,
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
                    'attachment_url' => $template->attachment_url,
                    'attachment_name' => $template->attachment_name,
                    'attachment_mime' => $template->attachment_mime,
                    'attachment_size' => $template->attachment_size,
                ],
            ],
        ]);
    }

    public function pause(BulkWhatsAppCampaign $campaign): JsonResponse
    {
        $this->authorizeBulkWhatsAppSend();
        abort_403((int) $campaign->company_id !== (int) company()->id);

        if (!in_array($campaign->status, ['queued', 'running'], true)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Only queued or running campaigns can be paused.',
            ], 422);
        }

        $campaign->forceFill(['status' => 'paused'])->saveQuietly();
        $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign paused.',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    public function resume(BulkWhatsAppCampaign $campaign): JsonResponse
    {
        $this->authorizeBulkWhatsAppSend();
        abort_403((int) $campaign->company_id !== (int) company()->id);

        if ($campaign->status !== 'paused') {
            return response()->json([
                'status' => 'fail',
                'message' => 'Only paused campaigns can be resumed.',
            ], 422);
        }

        $campaign->forceFill(['status' => 'running'])->saveQuietly();
        $this->dispatchCampaignProcessor($campaign);
        $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign resumed.',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    public function stop(BulkWhatsAppCampaign $campaign): JsonResponse
    {
        $this->authorizeBulkWhatsAppSend();
        abort_403((int) $campaign->company_id !== (int) company()->id);

        if (in_array($campaign->status, ['completed', 'failed', 'stopped'], true)) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Campaign is already finished.',
            ], 422);
        }

        $campaign->forceFill([
            'status' => 'stopped',
            'last_error' => 'Stopped by user',
        ])->saveQuietly();
        $campaign->refreshProgress();
        $campaign->load(['template', 'recipients.lead']);

        return response()->json([
            'status' => 'success',
            'message' => 'Campaign stopped.',
            'data' => [
                'campaign' => $this->campaignPayload($campaign),
                'summary' => $this->campaignSummary($campaign),
                'logs' => $this->campaignLogsPayload($campaign),
            ],
        ]);
    }

    private function resolveSelectedCampaignPayload(
        Request $request,
        BulkWhatsAppService $bulkService,
        bool $includeDelay = false
    ): array {
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

        $attachment = $this->resolveAttachmentForRequest($request, $template, $bulkService, $includeDelay);
        $hasAttachment = $attachment !== null
            || $request->hasFile('attachment')
            || ($template && filled($template->attachment_path));

        $leads = $this->selectedLeads($leadIds);
        $filters = $this->selectedFilters($request);
        $delayMinSeconds = max(1, (int) $request->input('delay_min_seconds', 8));
        $delayMaxSeconds = max($delayMinSeconds, (int) $request->input('delay_max_seconds', 20));

        return [$leads, $template, $message, $filters, $attachment, $hasAttachment, $delayMinSeconds, $delayMaxSeconds];
    }

    private function resolveAttachmentForRequest(Request $request, ?BulkWhatsAppTemplate $template, BulkWhatsAppService $bulkService, bool $persistUpload): ?array
    {
        if ($persistUpload && $request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            return $bulkService->storeUploadedAttachment(
                $request->file('attachment'),
                'bulk-whatsapp/campaigns/' . company()->id . '/' . user()->id . '/' . Str::uuid()
            );
        }

        if ($request->hasFile('attachment')) {
            return null;
        }

        if ($template && filled($template->attachment_path)) {
            return $bulkService->templateAttachmentMeta($template);
        }

        return null;
    }

    private function dispatchCampaignProcessor(BulkWhatsAppCampaign $campaign): void
    {
        ProcessBulkWhatsAppCampaignJob::dispatch((int) $campaign->id);
    }

    private function attachmentPayload(?array $attachment): ?array
    {
        if (!$attachment) {
            return null;
        }

        return [
            'url' => $attachment['url'] ?? null,
            'name' => $attachment['name'] ?? null,
            'mime' => $attachment['mime'] ?? null,
            'size' => $attachment['size'] ?? null,
        ];
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
            'products_services' => $request->input('products_services', []),
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
            'pending' => (int) ($progress['pending'] ?? 0),
            'status' => $campaign->status,
            'progress' => (int) ($progress['progress'] ?? 0),
        ];
    }

    private function campaignPayload(BulkWhatsAppCampaign $campaign): array
    {
        $attachment = $campaign->attachment_path ? [
            'url' => Storage::disk('public')->url($campaign->attachment_path),
            'name' => $campaign->attachment_name,
            'mime' => $campaign->attachment_mime,
            'size' => $campaign->attachment_size,
        ] : null;

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status,
            'session_key' => $campaign->session_key,
            'recipient_count' => (int) $campaign->recipient_count,
            'sent_count' => (int) $campaign->sent_count,
            'failed_count' => (int) $campaign->failed_count,
            'delay_min_seconds' => (int) ($campaign->delay_min_seconds ?: 8),
            'delay_max_seconds' => (int) ($campaign->delay_max_seconds ?: 20),
            'attachment' => $attachment,
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
                    'content_type' => $recipient->response_data['content_type'] ?? 'text',
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

    private function authorizeBulkWhatsAppView(): void
    {
        $leadPermission = user()->permission('view_lead');
        $bulkPermission = user()->permission('view_bulk_whatsapp');

        abort_403(
            !in_array($leadPermission, ['all', 'added', 'owned', 'both'], true)
            || $bulkPermission !== 'all'
        );
    }

    private function authorizeBulkWhatsAppSend(): void
    {
        $this->authorizeBulkWhatsAppView();
        abort_403(user()->permission('send_bulk_whatsapp') !== 'all');
    }

    private function canAccessLead(?Lead $lead): bool
    {
        if (!$lead) {
            return false;
        }

        if ($this->isAdminUser() || user()->permission('view_lead') === 'all') {
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
