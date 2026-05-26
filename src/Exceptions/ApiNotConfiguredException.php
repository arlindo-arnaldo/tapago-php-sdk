<?php

namespace TaPago\Exceptions;

class ApiNotConfiguredException extends TaPagoException
{
    /**
     * @param array<int, array{code?: string, message?: string}> $errors
     */
    public function __construct(string $message = 'API não configurada. Ative a API e defina o método de pagamento na sua conta.', array $errors = [])
    {
        parent::__construct($message, 403, 'API_NOT_CONFIGURED', $errors);
    }
}
