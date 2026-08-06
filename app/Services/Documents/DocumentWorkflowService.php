<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;
use App\Models\DocumentWorkflow;
use App\Models\DocumentWorkflowData;
use Illuminate\Support\Str;

class DocumentWorkflowService
{
    protected DocumentTemplateRenderService $templateRenderService;
    protected DocumentStatusResolver $statusResolver;
    protected DocumentNumberService $documentNumberService;
    protected DocumentAuditService $documentAuditService;

    public function __construct(
        DocumentTemplateRenderService $templateRenderService,
        DocumentStatusResolver $statusResolver,
        DocumentNumberService $documentNumberService,
        DocumentAuditService $documentAuditService
    ) {
        $this->templateRenderService = $templateRenderService;
        $this->statusResolver = $statusResolver;
        $this->documentNumberService = $documentNumberService;
        $this->documentAuditService = $documentAuditService;
    }

    public function createDraft(array $payload = [], array $mergeData = []): DocumentWorkflow
    {
        $template = !empty($payload['template_id'])
            ? DocumentTemplate::find($payload['template_id'])
            : null;

        $statuses = $this->statusResolver->initialStatuses($template);
        $renderedHtml = $template
            ? $this->templateRenderService->render($template, $mergeData)
            : ($payload['generated_html'] ?? null);

        $workflow = DocumentWorkflow::create(array_merge($statuses, [
            'company_id' => company()->id,
            'template_id' => $template?->id,
            'document_number' => $payload['document_number'] ?? $this->documentNumberService->nextNumber(),
            'original_document_number' => $payload['original_document_number'] ?? null,
            'title' => $payload['title'],
            'subject' => $payload['subject'] ?? $template?->subject,
            'category' => $payload['category'] ?? $template?->category,
            'document_type' => $payload['document_type'] ?? $template?->document_type ?? 'custom',
            'module_context' => $payload['module_context'] ?? 'manual',
            'context_id' => $payload['context_id'] ?? null,
            'owner_id' => $payload['owner_id'] ?? null,
            'client_id' => $payload['client_id'] ?? null,
            'project_id' => $payload['project_id'] ?? null,
            'generated_html' => $renderedHtml,
            'verification_hash' => (string) Str::uuid(),
            'expires_at' => $payload['expires_at'] ?? null,
            'created_by' => user()->id,
            'last_updated_by' => user()->id,
        ]));

        DocumentWorkflowData::updateOrCreate(
            ['document_workflow_id' => $workflow->id],
            [
                'company_id' => company()->id,
                'data_json' => $mergeData ? json_encode($mergeData) : null,
            ]
        );

        $this->documentAuditService->log($workflow, 'created', [
            'template_id' => $template?->id,
            'document_type' => $workflow->document_type,
        ]);

        return $workflow;
    }

    public function send(DocumentWorkflow $workflow): DocumentWorkflow
    {
        $workflow->fill(array_merge(
            $this->statusResolver->sentStatuses($workflow),
            [
                'sent_at' => now(),
                'last_updated_by' => user()->id,
            ]
        ));
        $workflow->save();

        $this->documentAuditService->log($workflow, 'sent');

        return $workflow;
    }

    public function cancel(DocumentWorkflow $workflow): DocumentWorkflow
    {
        $workflow->status = DocumentWorkflow::STATUS_CANCELLED;
        $workflow->last_updated_by = user()->id;
        $workflow->save();

        $this->documentAuditService->log($workflow, 'cancelled');

        return $workflow;
    }
}
