<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\CustomPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomPageController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Custom Pages';

        $this->middleware(function ($request, $next) {
            abort_403(!in_array('admin', user_roles()));

            return $next($request);
        });
    }

    public function index()
    {
        $this->customPages = CustomPage::where('company_id', company()->id)->latest()->get();

        return view('custom-pages.index', $this->data);
    }

    public function create()
    {
        return view('custom-pages.create', $this->data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'page_title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('custom_pages', 'slug')->where(fn ($query) => $query->where('company_id', company()->id)),
            ],
            'content' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        CustomPage::create([
            'company_id' => company()->id,
            'added_by' => user()->id,
            'page_title' => $request->page_title,
            'slug' => Str::slug($request->slug ?: $request->page_title),
            'content' => $request->content,
            'status' => $request->status,
        ]);

        return Reply::success(__('messages.recordSaved'));
    }

    public function edit(CustomPage $customPage)
    {
        abort_403($customPage->company_id !== company()->id);

        return view('custom-pages.edit', $this->data + ['customPage' => $customPage]);
    }

    public function update(Request $request, CustomPage $customPage)
    {
        abort_403($customPage->company_id !== company()->id);

        $request->validate([
            'page_title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('custom_pages', 'slug')
                    ->where(fn ($query) => $query->where('company_id', company()->id))
                    ->ignore($customPage->id),
            ],
            'content' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        $customPage->update([
            'page_title' => $request->page_title,
            'slug' => Str::slug($request->slug ?: $request->page_title),
            'content' => $request->content,
            'status' => $request->status,
        ]);

        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroy(CustomPage $customPage)
    {
        abort_403($customPage->company_id !== company()->id);

        $customPage->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }
}
