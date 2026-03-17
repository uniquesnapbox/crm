<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\AccountBaseController;
use App\Models\DocumentTemplate;
use App\Services\Documents\DocumentPermissionService;
use App\Services\Documents\DocumentTemplateRenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentTemplateController extends AccountBaseController
{
    protected DocumentPermissionService $permissionService;
    protected DocumentTemplateRenderService $templateRenderService;

    public function __construct(
        DocumentPermissionService $permissionService,
        DocumentTemplateRenderService $templateRenderService
    ) {
        parent::__construct();
        $this->permissionService = $permissionService;
        $this->templateRenderService = $templateRenderService;
        $this->pageTitle = 'Document Templates';
        $this->middleware(function ($request, $next) {
            abort_403(!$this->permissionService->canAccessModule());

            return $next($request);
        });
    }

    public function index()
    {
        $this->templates = DocumentTemplate::latest('id')->paginate(15);

        return view('document-templates.index', $this->data);
    }

    public function create()
    {
        $this->template = new DocumentTemplate();
        $this->formAction = route('document-templates.store');
        $this->formMethod = 'POST';

        return view('document-templates.create', $this->data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'category' => 'required|string|max:191',
            'document_type' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'content_html' => 'required|string',
            'requires_approval' => 'nullable|boolean',
            'requires_signature' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $template = DocumentTemplate::create([
            'company_id' => company()->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(6)),
            'category' => $validated['category'],
            'document_type' => $validated['document_type'],
            'subject' => $validated['subject'],
            'content_html' => $validated['content_html'],
            'requires_approval' => $request->boolean('requires_approval'),
            'requires_signature' => $request->boolean('requires_signature'),
            'is_active' => $request->boolean('is_active', true),
            'version' => 1,
            'created_by' => user()->id,
            'last_updated_by' => user()->id,
        ]);

        return redirect()->route('document-templates.show', $template)->with('success', 'Document template created successfully.');
    }

    public function show($id)
    {
        $this->template = DocumentTemplate::withCount('workflows')->findOrFail($id);
        $this->mergeTags = $this->templateRenderService->extractMergeTags($this->template->content_html);

        return view('document-templates.show', $this->data);
    }

    public function edit($id)
    {
        $this->template = DocumentTemplate::findOrFail($id);
        $this->formAction = route('document-templates.update', $this->template);
        $this->formMethod = 'PUT';

        return view('document-templates.create', $this->data);
    }

    public function update(Request $request, $id)
    {
        $template = DocumentTemplate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'category' => 'required|string|max:191',
            'document_type' => 'required|string|max:191',
            'subject' => 'required|string|max:191',
            'content_html' => 'required|string',
            'requires_approval' => 'nullable|boolean',
            'requires_signature' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'document_type' => $validated['document_type'],
            'subject' => $validated['subject'],
            'content_html' => $validated['content_html'],
            'requires_approval' => $request->boolean('requires_approval'),
            'requires_signature' => $request->boolean('requires_signature'),
            'is_active' => $request->boolean('is_active', true),
            'last_updated_by' => user()->id,
        ]);

        return redirect()->route('document-templates.show', $template)->with('success', 'Document template updated successfully.');
    }

    public function destroy($id)
    {
        $template = DocumentTemplate::findOrFail($id);
        $template->delete();

        return redirect()->route('document-templates.index')->with('success', 'Document template deleted successfully.');
    }

    public function duplicate($id)
    {
        $template = DocumentTemplate::findOrFail($id);
        $duplicate = $template->replicate(['slug']);
        $duplicate->name = $template->name . ' Copy';
        $duplicate->slug = Str::slug($duplicate->name) . '-' . Str::lower(Str::random(6));
        $duplicate->created_by = user()->id;
        $duplicate->last_updated_by = user()->id;
        $duplicate->save();

        return redirect()->route('document-templates.edit', $duplicate)->with('success', 'Document template duplicated successfully.');
    }

    public function preview($id)
    {
        $template = DocumentTemplate::findOrFail($id);

        return response($template->content_html);
    }
}
