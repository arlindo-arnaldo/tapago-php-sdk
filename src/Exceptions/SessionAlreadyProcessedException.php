<?php

namespace TaPago\Exceptions;

class SessionAlreadyProcessedException extends TaPagoException
{
    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(string $message = 'Esta sessão já foi processada anteriormente.', array $errors = [])
    {
        parent::__construct($message, 409, 'SESSION_ALREADY_PROCESSED', $errors);
    }
}
