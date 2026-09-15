<?php

namespace App\Http\Controllers;

use App\Models\CustomPage;
use App\Models\Company;

class PublicCustomPageController extends Controller
{
    public function show(string $slug)
    {
        $customPage = CustomPage::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $this->pageTitle = $customPage->page_title;
        $this->company = Company::find($customPage->company_id);

        return view('public.custom-page', $this->data + ['customPage' => $customPage]);
    }
}
