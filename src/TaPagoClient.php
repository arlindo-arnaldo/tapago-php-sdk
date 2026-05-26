<?php

namespace TaPago;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use TaPago\Exceptions\ApiNotConfiguredException;
use TaPago\Exceptions\DuplicateExternalRefException;
use TaPago\Exceptions\InsufficientCreditsException;
use TaPago\Exceptions\RateLimitExceededException;
use TaPago\Exceptions\SessionAlreadyProcessedException;
use TaPago\Exceptions\SessionNotFoundException;
use TaPago\Exceptions\TaPagoException;
use TaPago\Exceptions\ValidationFailedException;
use TaPago\Models\PaymentSession;
use TaPago\Models\PaymentSessionList;
use TaPago\Models\ReceiptValidationResult;

class TaPagoClient
{
    private string $baseUrl;
    private string $apiToken;
    private int $timeout;
    private GuzzleClient $httpClient;

    public const DEFAULT_BASE_URL = 'https://tapago.app/api';

    public function __construct(
        string $apiToken,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?GuzzleClient $httpClient = null,
        int $timeout = 30
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiToken = $apiToken;
        $this->timeout = $timeout;
        $this->httpClient = $httpClient ?? $this->createDefaultClient();
    }

    private function createDefaultClient(): GuzzleClient
    {
        return new GuzzleClient([
            'base_uri' => $this->baseUrl,
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiToken,
            ],
        ]);
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    public function setApiToken(string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function createPaymentSession(string $externalRef, int $amount): PaymentSession
    {
        $response = $this->request('POST', '/v1/payments', [
            RequestOptions::JSON => [
                'external_ref' => $externalRef,
                'amount' => $amount,
            ],
        ]);

        $body = $this->decodeResponse($response);

        return PaymentSession::fromArray($body);
    }

    public function listPaymentSessions(?string $status = null): PaymentSessionList
    {
        $query = [];

        if ($status !== null) {
            $query['status'] = $status;
        }

        $response = $this->request('GET', '/v1/payments', [
            RequestOptions::QUERY => $query,
        ]);

        $body = $this->decodeResponse($response);

        return new PaymentSessionList($body);
    }

    public function getPaymentSession(string $id): PaymentSession
    {
        $response = $this->request('GET', "/v1/payments/{$id}");
        $body = $this->decodeResponse($response);

        return PaymentSession::fromArray($body);
    }

    public function uploadReceipt(string $id, string $filePath): ReceiptValidationResult
    {
        if (!file_exists($filePath)) {
            throw new TaPagoException("Ficheiro não encontrado: {$filePath}", 0, 'FILE_NOT_FOUND');
        }

        $response = $this->request('POST', "/v1/payments/{$id}/receipt", [
            RequestOptions::MULTIPART => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath),
                ],
            ],
        ]);

        $body = $this->decodeResponse($response);

        return ReceiptValidationResult::fromArray($body);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            $response = $this->httpClient->request($method, $uri, $options);
        } catch (GuzzleException $e) {
            throw new TaPagoException(
                'Erro de comunicação com a API: ' . $e->getMessage(),
                0,
                'CONNECTION_ERROR'
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            $body = $this->decodeResponse($response);
            $this->handleErrorResponse($response, $body);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function handleErrorResponse(ResponseInterface $response, array $body): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 422) {
            /** @var array<string, string[]> $validationErrors */
            $validationErrors = $body['errors'] ?? [];
            throw new ValidationFailedException(
                'Erros de validação.',
                $validationErrors
            );
        }

        if ($statusCode === 429) {
            $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: 0);

            throw new RateLimitExceededException(
                'Demasiados pedidos. Tente novamente mais tarde.',
                $retryAfter
            );
        }

        /** @var string $errorMessage */
        $errorMessage = $body['error'] ?? 'Erro desconhecido.';
        /** @var array<int, array{code?: string, message?: string}> $errors */
        $errors = $body['errors'] ?? [];

        $errorCode = null;
        if (!empty($errors) && isset($errors[0]['code'])) {
            $errorCode = $errors[0]['code'];
        }

        match ($errorCode) {
            'API_NOT_CONFIGURED' => throw new ApiNotConfiguredException($errorMessage, $errors),
            'DUPLICATE_EXTERNAL_REF' => throw new DuplicateExternalRefException($errorMessage, $errors),
            'SESSION_NOT_FOUND' => throw new SessionNotFoundException($errorMessage, $errors),
            'SESSION_ALREADY_PROCESSED' => throw new SessionAlreadyProcessedException($errorMessage, $errors),
            'INSUFFICIENT_CREDITS' => throw new InsufficientCreditsException($errorMessage, $errors),
            default => throw new TaPagoException($errorMessage, $statusCode, $errorCode, $errors),
        };
    }

    /** @return array<string, mixed> */
    private function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TaPagoException(
                'Resposta inválida da API: erro ao decodificar JSON.',
                0,
                'INVALID_JSON'
            );
        }

        if (!is_array($decoded)) {
            throw new TaPagoException(
                'Resposta inválida da API: formato inesperado.',
                0,
                'INVALID_RESPONSE'
            );
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
