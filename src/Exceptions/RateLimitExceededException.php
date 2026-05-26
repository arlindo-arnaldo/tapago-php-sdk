<?php

namespace TaPago\Exceptions;

class RateLimitExceededException extends TaPagoException
{
    private ?int $retryAfter;

    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(string $message = 'Demasiados pedidos. Tente novamente mais tarde.', ?int $retryAfter = null, array $errors = [])
    {
        parent::__construct($message, 429, null, $errors);

        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
