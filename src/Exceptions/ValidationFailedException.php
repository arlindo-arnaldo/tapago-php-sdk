<?php

namespace TaPago\Exceptions;

class ValidationFailedException extends TaPagoException
{
    /** @var array<string, string[]> */
    private array $validationErrors;

    /**
     * @param array<string, string[]> $validationErrors
     */
    public function __construct(string $message = 'Erros de validação.', array $validationErrors = [])
    {
        parent::__construct($message, 422, null, []);

        $this->validationErrors = $validationErrors;
    }

    /** @return array<string, string[]> */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
