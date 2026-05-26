<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TaPago\Exceptions\ApiNotConfiguredException;
use TaPago\Exceptions\DuplicateExternalRefException;
use TaPago\Exceptions\TaPagoException;
use TaPago\Exceptions\ValidationFailedException;
use TaPago\TaPagoClient;

$client = new TaPagoClient(
    'https://tapago.app/api',
    'seu-token-aqui'
);

try {
    $session = $client->createPaymentSession('pedido-001', 5000);

    echo "Sessão criada com sucesso!\n";
    echo "ID: {$session->getId()}\n";
    echo "Status: {$session->getStatus()}\n";
    echo "Montante: {$session->getAmount()} {$session->getCurrency()}\n";
    echo "Referência externa: {$session->getExternalRef()}\n";

    $instructions = $session->getPaymentInstructions();

    if ($instructions) {
        echo "\nInstruções de pagamento:\n";
        echo "Tipo: {$instructions->getType()}\n";

        if ($instructions->getNumber()) {
            echo "Número: {$instructions->getNumber()}\n";
        }

        if ($instructions->getIban()) {
            echo "IBAN: {$instructions->getIban()}\n";
        }

        if ($instructions->getName()) {
            echo "Nome: {$instructions->getName()}\n";
        }
    }
} catch (ApiNotConfiguredException $e) {
    echo "ERRO: API não configurada. Ative a API na sua conta.\n";
} catch (DuplicateExternalRefException $e) {
    echo "ERRO: Já existe uma sessão com a referência 'pedido-001'.\n";
} catch (ValidationFailedException $e) {
    echo "ERROS de validação:\n";
    print_r($e->getValidationErrors());
} catch (TaPagoException $e) {
    echo "ERRO: {$e->getMessage()}\n";
}
