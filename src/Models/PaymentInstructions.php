<?php

namespace TaPago\Models;

class PaymentInstructions
{
    private string $type;
    private ?string $number;
    private ?string $iban;
    private ?string $name;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        /** @var string $t */
        $t = $data['type'] ?? '';
        $this->type = $t;

        /** @var ?string $n */
        $n = $data['number'] ?? null;
        $this->number = $n;

        /** @var ?string $i */
        $i = $data['iban'] ?? null;
        $this->iban = $i;

        /** @var ?string $nm */
        $nm = $data['name'] ?? null;
        $this->name = $nm;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'number' => $this->number,
            'iban' => $this->iban,
            'name' => $this->name,
        ];
    }
}
