<?php

namespace TaPago\Models;

class ReceiptValidationResult
{
    private bool $valid;
    private string $sessionId;
    private string $status;
    private ?int $amount;
    private ?int $amountPaid;
    private ?string $error;

    /** @var array<int, array{code?: string, message?: string}> */
    private array $errors;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        /** @var bool $v */
        $v = $data['valid'] ?? false;
        $this->valid = $v;

        /** @var string $sid */
        $sid = $data['session_id'] ?? '';
        $this->sessionId = $sid;

        /** @var string $sts */
        $sts = $data['status'] ?? '';
        $this->status = $sts;

        /** @var ?int $a */
        $a = isset($data['amount']) ? $data['amount'] : null;
        $this->amount = $a;

        /** @var ?int $ap */
        $ap = isset($data['amount_paid']) ? $data['amount_paid'] : null;
        $this->amountPaid = $ap;

        /** @var ?string $e */
        $e = $data['error'] ?? null;
        $this->error = $e;

        /** @var array<int, array{code?: string, message?: string}> $errs */
        $errs = $data['errors'] ?? [];
        $this->errors = $errs;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getAmountPaid(): ?int
    {
        return $this->amountPaid;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    /** @return array<int, array{code?: string, message?: string}> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return string[] */
    public function getErrorCodes(): array
    {
        return array_map(
            fn (array $err) => $err['code'] ?? 'UNKNOWN',
            $this->errors
        );
    }
}
