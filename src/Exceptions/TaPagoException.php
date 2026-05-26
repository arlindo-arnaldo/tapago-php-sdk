<?php

namespace TaPago\Exceptions;

use RuntimeException;

class TaPagoException extends RuntimeException
{
    private ?string $errorCode;

    /** @var array<int, array{code?: string, message?: string}> */
    private array $errors;

    private int $statusCode;

    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        ?string $errorCode = null,
        array $errors = []
    ) {
        parent::__construct($message, $statusCode);

        $this->errorCode = $errorCode;
        $this->errors = $errors;
        $this->statusCode = $statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /** @return array<int, array{code?: string, message?: string}> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
