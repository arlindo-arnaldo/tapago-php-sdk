<?php

namespace TaPago\Exceptions;

class InsufficientCreditsException extends TaPagoException
{
    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(string $message = 'Sem créditos suficientes. Compra mais créditos para continuar.', array $errors = [])
    {
        parent::__construct($message, 402, 'INSUFFICIENT_CREDITS', $errors);
    }
}
