<?php

namespace App\Services\Documents;

use App\Models\DocumentTemplate;

class DocumentTemplateRenderService
{
    public function render(DocumentTemplate $template, array $data = []): string
    {
        $html = $template->content_html;

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $html = str_replace('{{' . $key . '}}', (string) $value, $html);
            $html = str_replace('{{ ' . $key . ' }}', (string) $value, $html);
        }

        return preg_replace('/{{\s*[\w\.]+\s*}}/', '', $html) ?? $html;
    }

    public function extractMergeTags(string $html): array
    {
        preg_match_all('/{{\s*([\w\.]+)\s*}}/', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
