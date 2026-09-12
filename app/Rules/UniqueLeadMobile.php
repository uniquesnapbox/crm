<?php

namespace App\Rules;

use App\Support\LeadMobile;
use Illuminate\Contracts\Validation\Rule;

class UniqueLeadMobile implements Rule
{
    public function __construct(
        private readonly int $companyId,
        private readonly ?int $ignoreLeadId = null,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        return LeadMobile::findExisting($this->companyId, (string) $value, $this->ignoreLeadId) === null;
    }

    public function message(): string
    {
        return LeadMobile::DUPLICATE_MESSAGE;
    }
}
