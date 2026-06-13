<?php

namespace App\Services\WhatsApp;

use RuntimeException;

class WascriptException extends RuntimeException
{
    private ?int $httpStatus;
    private mixed $responseBody;
    private ?string $normalizedPhone;

    public function __construct(
        string $message,
        ?int $httpStatus = null,
        mixed $responseBody = null,
        ?string $normalizedPhone = null
    ) {
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
        $this->normalizedPhone = $normalizedPhone;
        parent::__construct($message);
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function responseBody(): mixed
    {
        return $this->responseBody;
    }

    public function normalizedPhone(): ?string
    {
        return $this->normalizedPhone;
    }
}
