<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\AccountBaseController;
use App\Models\DocumentTemplate;
use App\Models\DocumentWorkflow;
use App\Models\Project;
use App\Models\User;
use App\Services\Documents\DocumentPermissionService;
use App\Services\Documents\DocumentTemplateRenderService;
use App\Services\Documents\DocumentWorkflowService;
use Illuminate\Http\Request;

class DocumentWorkflowController extends AccountBaseController
{
    protected DocumentPermissionService $permissionService;
    protected DocumentWorkflowService $workflowService;
    protected DocumentTemplateRenderService $templateRenderService;

    public function __construct(
        DocumentPermissionService $permissionService,
        DocumentWorkflowService $workflowService,
        DocumentTemplateRenderService $templateRenderService
    ) {
        parent::__construct();
        $this->permissionService = $permissionService;
        $this->workflowService = $workflowService;
        $this->templateRenderService = $templateRenderService;
        $this->pageTitle = 'Documents';
        $this->middleware(function ($request, $next) {
            abort_403(!$this->permissionService->canAccessModule());

            return $next($request);
        });
    }

    public function index()
    {
        $this->documents = DocumentWorkflow::with(['template', 'owner', 'client', 'project'])
            ->latest('id')
            ->paginate(15);

        return view('documents.index', $this->data);
    }

    public function create()
    {
        $this->document = new DocumentWorkflow();
        $this->templates = DocumentTemplate::where('is_active', true)->latest('id')->get();
        $this->clients = User::allClients();
        $this->owners = User::allEmployees();
        $this->projects = Project::allProjects();
        $this->formAction = route('documents.store');
        $this->formMethod = 'POST';

        return view('documents.create', $this->data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'nullable|exists:document_templates,id',
            'title' => 'required|string|max:191',
            'subject' => 'nullable|string|max:191',
            'document_type' => 'nullable|string|max:191',
            'module_context' => 'nullable|string|max:191',
            'owner_id' => 'nullable|exists:users,id',
            'client_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'expires_at' => 'nullable|date',
            'merge_data' => 'nullable|string',
        ]);

        $workflow = $this->workflowService->createDraft($validated, $this->decodeMergeData($request->input('merge_data')));

        return redirect()->route('documents.show', $workflow)->with('success', 'Document draft created successfully.');
    }

    public function show($id)
    {
        $this->document = DocumentWorkflow::with([
            'template',
            'owner',
            'client',
            'project',
            'workflowData',
            'auditLogs' => fn ($query) => $query->latest('id'),
        ])->findOrFail($id);

        abort_403(!$this->permissionService->canManageWorkflow($this->document));

        return view('documents.show', $this->data);
    }

    public function edit($id)
    {
        $this->document = DocumentWorkflow::with('workflowData')->findOrFail($id);
        abort_403(!$this->permissionService->canManageWorkflow($this->document));
        $this->templates = DocumentTemplate::where('is_active', true)->latest('id')->get();
        $this->clients = User::allClients();
        $this->owners = User::allEmployees();
        $this->projects = Project::allProjects();
        $this->formAction = route('documents.update', $this->document);
        $this->formMethod = 'PUT';

        return view('documents.create', $this->data);
    }

    public function update(Request $request, $id)
    {
        $document = DocumentWorkflow::with('workflowData')->findOrFail($id);
        abort_403(!$this->permissionService->canManageWorkflow($document));

        $validated = $request->validate([
            'template_id' => 'nullable|exists:document_templates,id',
            'title' => 'required|string|max:191',
            'subject' => 'nullable|string|max:191',
            'document_type' => 'nullable|string|max:191',
            'module_context' => 'nullable|string|max:191',
            'owner_id' => 'nullable|exists:users,id',
            'client_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'expires_at' => 'nullable|date',
            'merge_data' => 'nullable|string',
        ]);

        $template = !empty($validated['template_id']) ? DocumentTemplate::find($validated['template_id']) : null;
        $mergeData = $this->decodeMergeData($request->input('merge_data'));

        $document->fill([
            'template_id' => $validated['template_id'] ?? null,
            'title' => $validated['title'],
            'subject' => $validated['subject'] ?? $template?->subject,
            'document_type' => $validated['document_type'] ?? $template?->document_type ?? $document->document_type,
            'category' => $template?->category ?? $document->category,
            'module_context' => $validated['module_context'] ?? 'manual',
            'owner_id' => $validated['owner_id'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'generated_html' => $template
                ? $this->templateRenderService->render($template, $mergeData)
                : $document->generated_html,
            'expires_at' => $validated['expires_at'] ?? null,
            'last_updated_by' => user()->id,
        ]);
        $document->save();

        $document->workflowData()->updateOrCreate(
            ['document_workflow_id' => $document->id],
            ['company_id' => company()->id, 'data_json' => $mergeData ? json_encode($mergeData) : null]
        );

        return redirect()->route('documents.show', $document)->with('success', 'Document updated successfully.');
    }

    public function destroy($id)
    {
        $document = DocumentWorkflow::findOrFail($id);
        abort_403(!$this->permissionService->canManageWorkflow($document));
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }

    public function send($id)
    {
        $document = DocumentWorkflow::findOrFail($id);
        abort_403(!$this->permissionService->canManageWorkflow($document));
        $this->workflowService->send($document);

        return redirect()->route('documents.show', $document)->with('success', 'Document moved to the next workflow stage.');
    }

    public function cancel($id)
    {
        $document = DocumentWorkflow::findOrFail($id);
        abort_403(!$this->permissionService->canManageWorkflow($document));
        $this->workflowService->cancel($document);

        return redirect()->route('documents.show', $document)->with('success', 'Document cancelled successfully.');
    }

    public function downloadPdf($id)
    {
        return redirect()->route('documents.show', $id)->with('error', 'PDF generation will be enabled in the next phase.');
    }

    public function regeneratePdf($id)
    {
        return redirect()->route('documents.show', $id)->with('error', 'PDF regeneration is not enabled yet.');
    }

    public function timeline($id)
    {
        $document = DocumentWorkflow::with('auditLogs')->findOrFail($id);
        abort_403(!$this->permissionService->canManageWorkflow($document));

        return response()->json($document->auditLogs);
    }

    public function applyQuickAction(Request $request)
    {
        $validated = $request->validate([
            'action_type' => 'required|in:delete,cancel',
            'row_ids' => 'required|string',
        ]);

        $ids = collect(explode(',', $validated['row_ids']))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $documents = DocumentWorkflow::whereIn('id', $ids)->get();

        foreach ($documents as $document) {
            if (!$this->permissionService->canManageWorkflow($document)) {
                continue;
            }

            if ($validated['action_type'] === 'delete') {
                $document->delete();
            } else {
                $this->workflowService->cancel($document);
            }
        }

        return redirect()->route('documents.index')->with('success', 'Bulk action applied successfully.');
    }

    private function decodeMergeData(?string $mergeData): array
    {
        if (blank($mergeData)) {
            return [];
        }

        $decoded = json_decode($mergeData, true);

        abort_if(json_last_error() !== JSON_ERROR_NONE || !is_array($decoded), 422, 'Merge data must be valid JSON.');

        return $decoded;
    }
}
