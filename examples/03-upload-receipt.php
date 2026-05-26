<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TaPago\Exceptions\InsufficientCreditsException;
use TaPago\Exceptions\SessionAlreadyProcessedException;
use TaPago\Exceptions\SessionNotFoundException;
use TaPago\Exceptions\TaPagoException;
use TaPago\Exceptions\ValidationFailedException;
use TaPago\TaPagoClient;

$client = new TaPagoClient('seu-token-aqui');

$sessionId = 'uuid-da-sessao';
$receiptPath = '/caminho/para/comprovativo.pdf';

try {
    $result = $client->uploadReceipt($sessionId, $receiptPath);

    if ($result->isValid()) {
        echo "COMPROVATIVO VÁLIDO!\n";
        echo "Montante pago: {$result->getAmountPaid()} Kz\n";
        echo "Status: {$result->getStatus()}\n";
    } else {
        echo "COMPROVATIVO INVÁLIDO.\n";
        echo "Motivo: {$result->getError()}\n";
        echo "Códigos de erro: " . implode(', ', $result->getErrorCodes()) . "\n";
    }
} catch (SessionNotFoundException $e) {
    echo "ERRO: Sessão não encontrada.\n";
} catch (SessionAlreadyProcessedException $e) {
    echo "ERRO: Sessão já foi processada.\n";
} catch (InsufficientCreditsException $e) {
    echo "ERRO: Créditos insuficientes. Compra mais créditos.\n";
} catch (ValidationFailedException $e) {
    echo "ERRO de validação do ficheiro.\n";
} catch (TaPagoException $e) {
    echo "ERRO: {$e->getMessage()}\n";
}
