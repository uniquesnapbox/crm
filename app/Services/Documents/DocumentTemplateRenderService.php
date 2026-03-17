<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;

class DocumentTemplateRenderService
{
    public function render(DocumentTemplate $template, array $data = []): string
    {
        return $template->content_html;
    }
}
