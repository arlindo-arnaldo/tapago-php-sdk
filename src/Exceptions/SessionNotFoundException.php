<?php

namespace TaPago\Exceptions;

class SessionNotFoundException extends TaPagoException
{
    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(string $message = 'Sessão de pagamento não encontrada.', array $errors = [])
    {
        parent::__construct($message, 404, 'SESSION_NOT_FOUND', $errors);
    }
}
