<?php

namespace TaPago\Models;

class PaymentSessionList
{
    /** @var PaymentSession[] */
    private array $data;
    private int $currentPage;
    private int $lastPage;
    private int $perPage;
    private int $total;
    private ?int $from;
    private ?int $to;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(array $response)
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = $response['data'] ?? [];
        $this->data = array_map(
            fn (array $item) => PaymentSession::fromArray($item),
            $items
        );
        /** @var int $cp */
        $cp = $response['current_page'] ?? 1;
        $this->currentPage = $cp;

        /** @var int $lp */
        $lp = $response['last_page'] ?? 1;
        $this->lastPage = $lp;

        /** @var int $pp */
        $pp = $response['per_page'] ?? 15;
        $this->perPage = $pp;

        /** @var int $t */
        $t = $response['total'] ?? 0;
        $this->total = $t;

        /** @var ?int $f */
        $f = $response['from'] ?? null;
        $this->from = $f;

        /** @var ?int $t2 */
        $t2 = $response['to'] ?? null;
        $this->to = $t2;
    }

    /** @return PaymentSession[] */
    public function getData(): array
    {
        return $this->data;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getFrom(): ?int
    {
        return $this->from;
    }

    public function getTo(): ?int
    {
        return $this->to;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }
}
