<?php

namespace App\Http\Controllers;

use App\Models\CustomPage;

class PublicCustomPageController extends Controller
{
    public function show(string $slug)
    {
        $customPage = CustomPage::where('slug', $slug)
            ->where('company_id', company()->id)
            ->where('status', 'active')
            ->firstOrFail();

        $this->pageTitle = $customPage->page_title;

        return view('public.custom-page', $this->data + ['customPage' => $customPage]);
    }
}
