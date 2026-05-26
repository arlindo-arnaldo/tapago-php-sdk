<?php

namespace TaPago\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TaPago\Models\PaymentInstructions;
use TaPago\Models\PaymentSession;
use TaPago\Models\PaymentSessionList;
use TaPago\Models\ReceiptValidationResult;

class ModelsTest extends TestCase
{
    public function test_payment_instructions_from_express(): void
    {
        $data = ['type' => 'express', 'number' => '923456789', 'iban' => null, 'name' => null];
        $instructions = PaymentInstructions::fromArray($data);

        $this->assertSame('express', $instructions->getType());
        $this->assertSame('923456789', $instructions->getNumber());
        $this->assertNull($instructions->getIban());
        $this->assertNull($instructions->getName());
    }

    public function test_payment_instructions_from_iban(): void
    {
        $data = [
            'type' => 'iban',
            'number' => null,
            'iban' => 'AO06.0006.0000.2981.4243.3016.4',
            'name' => 'John Doe',
        ];
        $instructions = PaymentInstructions::fromArray($data);

        $this->assertSame('iban', $instructions->getType());
        $this->assertNull($instructions->getNumber());
        $this->assertSame('AO06.0006.0000.2981.4243.3016.4', $instructions->getIban());
        $this->assertSame('John Doe', $instructions->getName());
    }

    public function test_payment_session_creation(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'external_ref' => 'pedido-123',
            'amount' => 5000,
            'amount_paid' => 5000,
            'currency' => 'AOA',
            'status' => 'completed',
            'valid' => true,
            'payment_instructions' => [
                'type' => 'express',
                'number' => '923456789',
            ],
            'validated_at' => '2026-05-17T14:35:00.000000Z',
            'created_at' => '2026-05-17T14:30:00.000000Z',
        ];

        $session = PaymentSession::fromArray($data);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $session->getId());
        $this->assertSame('pedido-123', $session->getExternalRef());
        $this->assertSame(5000, $session->getAmount());
        $this->assertSame(5000, $session->getAmountPaid());
        $this->assertSame('AOA', $session->getCurrency());
        $this->assertSame('completed', $session->getStatus());
        $this->assertTrue($session->isValid());
        $this->assertNotNull($session->getPaymentInstructions());
        $this->assertSame('923456789', $session->getPaymentInstructions()->getNumber());
        $this->assertNotNull($session->getValidatedAt());
        $this->assertNotNull($session->getCreatedAt());
    }

    public function test_payment_session_without_amount_paid(): void
    {
        $data = [
            'id' => 'uuid',
            'external_ref' => 'ref',
            'amount' => 5000,
            'currency' => 'AOA',
            'status' => 'pending',
        ];

        $session = PaymentSession::fromArray($data);

        $this->assertNull($session->getAmountPaid());
        $this->assertNull($session->isValid());
        $this->assertNull($session->getPaymentInstructions());
        $this->assertNull($session->getValidatedAt());
        $this->assertNull($session->getCreatedAt());
    }

    public function test_payment_session_list(): void
    {
        $response = [
            'data' => [
                ['id' => '1', 'external_ref' => 'ref-1', 'amount' => 1000, 'currency' => 'AOA', 'status' => 'pending'],
                ['id' => '2', 'external_ref' => 'ref-2', 'amount' => 2000, 'currency' => 'AOA', 'status' => 'completed'],
            ],
            'current_page' => 1,
            'last_page' => 3,
            'per_page' => 15,
            'total' => 35,
            'from' => 1,
            'to' => 15,
        ];

        $list = new PaymentSessionList($response);

        $this->assertCount(2, $list->getData());
        $this->assertSame(1, $list->getCurrentPage());
        $this->assertSame(3, $list->getLastPage());
        $this->assertSame(15, $list->getPerPage());
        $this->assertSame(35, $list->getTotal());
        $this->assertSame(1, $list->getFrom());
        $this->assertSame(15, $list->getTo());
        $this->assertTrue($list->hasMorePages());
        $this->assertFalse($list->isEmpty());
    }

    public function test_payment_session_list_empty(): void
    {
        $response = [
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 15,
            'total' => 0,
            'from' => null,
            'to' => null,
        ];

        $list = new PaymentSessionList($response);

        $this->assertCount(0, $list->getData());
        $this->assertFalse($list->hasMorePages());
        $this->assertTrue($list->isEmpty());
    }

    public function test_receipt_validation_result_success(): void
    {
        $data = [
            'valid' => true,
            'session_id' => 'uuid-sessao',
            'status' => 'completed',
            'amount' => 5000,
            'amount_paid' => 5000,
        ];

        $result = ReceiptValidationResult::fromArray($data);

        $this->assertTrue($result->isValid());
        $this->assertSame('uuid-sessao', $result->getSessionId());
        $this->assertSame('completed', $result->getStatus());
        $this->assertSame(5000, $result->getAmount());
        $this->assertSame(5000, $result->getAmountPaid());
        $this->assertNull($result->getError());
        $this->assertEmpty($result->getErrors());
    }

    public function test_receipt_validation_result_failure(): void
    {
        $data = [
            'valid' => false,
            'session_id' => 'uuid-sessao',
            'status' => 'failed',
            'error' => 'Pagamento inválido.',
            'errors' => [
                ['code' => 'AMOUNT_MISMATCH', 'message' => 'O montante do comprovativo é inferior ao esperado.'],
            ],
        ];

        $result = ReceiptValidationResult::fromArray($data);

        $this->assertFalse($result->isValid());
        $this->assertSame('failed', $result->getStatus());
        $this->assertNull($result->getAmount());
        $this->assertNull($result->getAmountPaid());
        $this->assertSame('Pagamento inválido.', $result->getError());
        $this->assertCount(1, $result->getErrors());
        $this->assertContains('AMOUNT_MISMATCH', $result->getErrorCodes());
    }
}
