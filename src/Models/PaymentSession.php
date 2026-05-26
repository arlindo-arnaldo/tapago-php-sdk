<?php

namespace TaPago\Models;

class PaymentSession
{
    private string $id;
    private string $externalRef;
    private int $amount;
    private ?int $amountPaid;
    private string $currency;
    private string $status;
    private ?bool $valid;
    private ?PaymentInstructions $paymentInstructions;
    private ?string $validatedAt;
    private ?string $createdAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        /** @var string $id */
        $id = $data['id'] ?? '';
        $this->id = $id;

        /** @var string $ref */
        $ref = $data['external_ref'] ?? '';
        $this->externalRef = $ref;

        /** @var int $amt */
        $amt = $data['amount'] ?? 0;
        $this->amount = $amt;

        /** @var ?int $paid */
        $paid = isset($data['amount_paid']) ? $data['amount_paid'] : null;
        $this->amountPaid = $paid;

        /** @var string $cur */
        $cur = $data['currency'] ?? 'AOA';
        $this->currency = $cur;

        /** @var string $sts */
        $sts = $data['status'] ?? '';
        $this->status = $sts;

        /** @var ?bool $v */
        $v = $data['valid'] ?? null;
        $this->valid = $v;

        /** @var array<string, mixed>|null $pi */
        $pi = $data['payment_instructions'] ?? null;
        $this->paymentInstructions = $pi !== null ? PaymentInstructions::fromArray($pi) : null;

        /** @var ?string $va */
        $va = $data['validated_at'] ?? null;
        $this->validatedAt = $va;

        /** @var ?string $ca */
        $ca = $data['created_at'] ?? null;
        $this->createdAt = $ca;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getExternalRef(): string
    {
        return $this->externalRef;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getAmountPaid(): ?int
    {
        return $this->amountPaid;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function getPaymentInstructions(): ?PaymentInstructions
    {
        return $this->paymentInstructions;
    }

    public function getValidatedAt(): ?string
    {
        return $this->validatedAt;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'external_ref' => $this->externalRef,
            'amount' => $this->amount,
            'amount_paid' => $this->amountPaid,
            'currency' => $this->currency,
            'status' => $this->status,
            'valid' => $this->valid,
            'payment_instructions' => $this->paymentInstructions?->toArray(),
            'validated_at' => $this->validatedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
