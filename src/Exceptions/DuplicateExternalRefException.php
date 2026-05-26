<?php

namespace TaPago\Exceptions;

class DuplicateExternalRefException extends TaPagoException
{
    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(string $message = 'Já existe uma sessão com esta referência externa.', array $errors = [])
    {
        parent::__construct($message, 409, 'DUPLICATE_EXTERNAL_REF', $errors);
    }
}
