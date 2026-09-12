<?php

namespace App\Support;

use App\Models\Lead;
use Throwable;

final class LeadMobile
{
    public const DUPLICATE_MESSAGE = 'This mobile number already exists in another lead.';

    public static function normalize(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);

        return $digits === '' ? null : $digits;
    }

    public static function findExisting(int $companyId, ?string $mobile, ?int $ignoreLeadId = null): ?Lead
    {
        $normalized = self::normalize($mobile);

        if ($normalized === null) {
            return null;
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->when($ignoreLeadId !== null, fn ($query) => $query->where('id', '<>', $ignoreLeadId))
            ->whereRaw("REGEXP_REPLACE(COALESCE(mobile, ''), '[^0-9]', '') = ?", [$normalized])
            ->orderBy('id')
            ->first();
    }

    public static function isDuplicateException(Throwable $exception): bool
    {
        return str_contains($exception->getMessage(), self::DUPLICATE_MESSAGE)
            || str_contains($exception->getMessage(), 'leads_company_mobile_unique');
    }
}
