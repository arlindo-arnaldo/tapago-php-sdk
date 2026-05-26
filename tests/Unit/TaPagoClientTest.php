<?php

namespace TaPago\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TaPago\Exceptions\ApiNotConfiguredException;
use TaPago\Exceptions\DuplicateExternalRefException;
use TaPago\Exceptions\InsufficientCreditsException;
use TaPago\Exceptions\RateLimitExceededException;
use TaPago\Exceptions\SessionAlreadyProcessedException;
use TaPago\Exceptions\SessionNotFoundException;
use TaPago\Exceptions\TaPagoException;
use TaPago\Exceptions\ValidationFailedException;
use TaPago\TaPagoClient;

class TaPagoClientTest extends TestCase
{
    private function createMockClient(array $responses): TaPagoClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $handlerStack, 'http_errors' => false]);

        return new TaPagoClient('test-token', 'https://tapago.app/api', $guzzle);
    }

    private function jsonResponse(int $status, array $data): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    public function test_create_payment_session_success(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(201, [
                'id' => 'uuid-123',
                'status' => 'pending',
                'amount' => 5000,
                'currency' => 'AOA',
                'external_ref' => 'pedido-001',
                'payment_instructions' => [
                    'type' => 'express',
                    'number' => '923456789',
                ],
            ]),
        ]);

        $session = $client->createPaymentSession('pedido-001', 5000);

        $this->assertSame('uuid-123', $session->getId());
        $this->assertSame('pending', $session->getStatus());
        $this->assertSame(5000, $session->getAmount());
        $this->assertSame('pedido-001', $session->getExternalRef());
        $this->assertSame('923456789', $session->getPaymentInstructions()->getNumber());
    }

    public function test_create_payment_session_api_not_configured(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(403, [
                'error' => 'API não configurada.',
                'errors' => [
                    ['code' => 'API_NOT_CONFIGURED', 'message' => 'Ative a API e defina o método de pagamento.'],
                ],
            ]),
        ]);

        $this->expectException(ApiNotConfiguredException::class);
        $client->createPaymentSession('ref', 1000);
    }

    public function test_create_payment_session_duplicate_ref(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(409, [
                'error' => 'Conflito.',
                'errors' => [
                    ['code' => 'DUPLICATE_EXTERNAL_REF', 'message' => 'Já existe uma sessão com esta referência.'],
                ],
            ]),
        ]);

        $this->expectException(DuplicateExternalRefException::class);
        $client->createPaymentSession('dup-ref', 1000);
    }

    public function test_create_payment_session_validation_error(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(422, [
                'errors' => [
                    'external_ref' => ['O campo external_ref é obrigatório.'],
                    'amount' => ['O campo amount deve ser um número inteiro.'],
                ],
            ]),
        ]);

        $this->expectException(ValidationFailedException::class);
        $client->createPaymentSession('', 0);
    }

    public function test_list_payment_sessions(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'data' => [
                    ['id' => '1', 'external_ref' => 'ref-1', 'amount' => 1000, 'currency' => 'AOA', 'status' => 'pending'],
                    ['id' => '2', 'external_ref' => 'ref-2', 'amount' => 2000, 'currency' => 'AOA', 'status' => 'completed'],
                ],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 2,
            ]),
        ]);

        $list = $client->listPaymentSessions();

        $this->assertCount(2, $list->getData());
        $this->assertSame(2, $list->getTotal());
    }

    public function test_list_payment_sessions_with_status_filter(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'data' => [
                    ['id' => '2', 'external_ref' => 'ref-2', 'amount' => 2000, 'currency' => 'AOA', 'status' => 'completed'],
                ],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 15,
                'total' => 1,
            ]),
        ]);

        $list = $client->listPaymentSessions(status: 'completed');

        $this->assertCount(1, $list->getData());
        $this->assertSame('completed', $list->getData()[0]->getStatus());
    }

    public function test_get_payment_session_success(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'id' => 'uuid-123',
                'external_ref' => 'pedido-001',
                'amount' => 5000,
                'amount_paid' => 5000,
                'currency' => 'AOA',
                'status' => 'completed',
                'valid' => true,
                'payment_instructions' => ['type' => 'express', 'number' => '923456789'],
                'validated_at' => '2026-05-17T14:35:00.000000Z',
                'created_at' => '2026-05-17T14:30:00.000000Z',
            ]),
        ]);

        $session = $client->getPaymentSession('uuid-123');

        $this->assertSame('uuid-123', $session->getId());
        $this->assertSame('completed', $session->getStatus());
        $this->assertTrue($session->isValid());
        $this->assertSame(5000, $session->getAmountPaid());
    }

    public function test_get_payment_session_not_found(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(404, [
                'error' => 'Sessão não encontrada.',
                'errors' => [
                    ['code' => 'SESSION_NOT_FOUND', 'message' => 'A sessão de pagamento não foi encontrada.'],
                ],
            ]),
        ]);

        $this->expectException(SessionNotFoundException::class);
        $client->getPaymentSession('invalid-uuid');
    }

    public function test_upload_receipt_success(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'valid' => true,
                'session_id' => 'uuid-123',
                'status' => 'completed',
                'amount' => 5000,
                'amount_paid' => 5000,
            ]),
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_receipt_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 fake pdf content');

        try {
            $result = $client->uploadReceipt('uuid-123', $tempFile);

            $this->assertTrue($result->isValid());
            $this->assertSame('uuid-123', $result->getSessionId());
            $this->assertSame('completed', $result->getStatus());
            $this->assertSame(5000, $result->getAmountPaid());
        } finally {
            unlink($tempFile);
        }
    }

    public function test_upload_receipt_business_error(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(200, [
                'valid' => false,
                'session_id' => 'uuid-123',
                'status' => 'failed',
                'error' => 'Comprovativo inválido.',
                'errors' => [
                    ['code' => 'AMOUNT_MISMATCH', 'message' => 'O montante do comprovativo é inferior ao esperado.'],
                ],
            ]),
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_receipt_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 fake pdf content');

        try {
            $result = $client->uploadReceipt('uuid-123', $tempFile);

            $this->assertFalse($result->isValid());
            $this->assertSame('failed', $result->getStatus());
            $this->assertContains('AMOUNT_MISMATCH', $result->getErrorCodes());
        } finally {
            unlink($tempFile);
        }
    }

    public function test_upload_receipt_session_not_found(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(404, [
                'error' => 'Sessão não encontrada.',
                'errors' => [
                    ['code' => 'SESSION_NOT_FOUND', 'message' => 'Sessão não encontrada.'],
                ],
            ]),
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_receipt_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 fake pdf');

        try {
            $this->expectException(SessionNotFoundException::class);
            $client->uploadReceipt('invalid-uuid', $tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_upload_receipt_already_processed(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(409, [
                'valid' => false,
                'session_id' => 'uuid-123',
                'status' => 'completed',
                'error' => 'Sessão já processada.',
                'errors' => [
                    ['code' => 'SESSION_ALREADY_PROCESSED', 'message' => 'Sessão já processada.'],
                ],
            ]),
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_receipt_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 fake pdf');

        try {
            $this->expectException(SessionAlreadyProcessedException::class);
            $client->uploadReceipt('uuid-123', $tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_upload_receipt_insufficient_credits(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(402, [
                'valid' => false,
                'session_id' => 'uuid-123',
                'status' => 'failed',
                'error' => 'Créditos insuficientes.',
                'errors' => [
                    ['code' => 'INSUFFICIENT_CREDITS', 'message' => 'Sem créditos.'],
                ],
            ]),
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_receipt_') . '.pdf';
        file_put_contents($tempFile, '%PDF-1.4 fake pdf');

        try {
            $this->expectException(InsufficientCreditsException::class);
            $client->uploadReceipt('uuid-123', $tempFile);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_upload_receipt_file_not_found(): void
    {
        $client = $this->createMockClient([]);

        $this->expectException(TaPagoException::class);
        $this->expectExceptionMessage('Ficheiro não encontrado');
        $client->uploadReceipt('uuid-123', '/nonexistent/file.pdf');
    }

    public function test_rate_limit_exceeded(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(429, ['error' => 'Too Many Requests']),
        ]);

        $this->expectException(RateLimitExceededException::class);
        $client->listPaymentSessions();
    }

    public function test_unknown_error_code(): void
    {
        $client = $this->createMockClient([
            $this->jsonResponse(500, [
                'error' => 'Internal server error.',
                'errors' => [['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno.']],
            ]),
        ]);

        $this->expectException(TaPagoException::class);
        $client->listPaymentSessions();
    }
}
