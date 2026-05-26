<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TaPago\Exceptions\TaPagoException;
use TaPago\TaPagoClient;

$client = new TaPagoClient(
    'https://tapago.app/api',
    'seu-token-aqui'
);

try {
    // Listar todas as sessões
    $list = $client->listPaymentSessions();

    echo "Total de sessões: {$list->getTotal()}\n";
    echo "Página {$list->getCurrentPage()} de {$list->getLastPage()}\n\n";

    foreach ($list->getData() as $session) {
        echo "[{$session->getStatus()}] {$session->getId()}\n";
        echo "  Ref: {$session->getExternalRef()}\n";
        echo "  Montante: {$session->getAmount()} {$session->getCurrency()}\n";
        echo "  Criado em: {$session->getCreatedAt()}\n\n";
    }

    // Filtrar por status
    echo "--- Sessões completadas ---\n";
    $completed = $client->listPaymentSessions(status: 'completed');

    foreach ($completed->getData() as $session) {
        echo "- {$session->getExternalRef()}: {$session->getAmount()} Kz\n";
    }
} catch (TaPagoException $e) {
    echo "ERRO: {$e->getMessage()}\n";
}
