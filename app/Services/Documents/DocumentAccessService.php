<?php

namespace App\Services\Documents;

use App\Models\DocumentAccessToken;

class DocumentAccessService
{
    public function issueToken(array $payload = []): ?DocumentAccessToken
    {
        return null;
    }

    public function validateToken(string $token): ?DocumentAccessToken
    {
        return null;
    }
}
