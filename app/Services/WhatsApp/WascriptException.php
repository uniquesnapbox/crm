<?php

namespace App\Services\WhatsApp;

use RuntimeException;

class WascriptException extends RuntimeException
{
    private ?int $httpStatus;
    private mixed $responseBody;

    public function __construct(
        string $message,
        ?int $httpStatus = null,
        mixed $responseBody = null
    ) {
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
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
}
