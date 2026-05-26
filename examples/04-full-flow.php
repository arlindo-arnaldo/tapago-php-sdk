<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TaPago\Exceptions\TaPagoException;
use TaPago\TaPagoClient;

$client = new TaPagoClient(
    'https://tapago.app/api',
    'seu-token-aqui'
);

$externalRef = 'pedido-' . time();
$amount = 10000;

try {
    echo "1. Criar sessão de pagamento...\n";
    $session = $client->createPaymentSession($externalRef, $amount);
    echo "   Sessão criada: {$session->getId()}\n\n";

    $instructions = $session->getPaymentInstructions();

    echo "2. Instruções para o cliente:\n";

    if ($instructions->getType() === 'express') {
        echo "   Faça uma transferência Multicaixa Express para o número:\n";
        echo "   {$instructions->getNumber()}\n";
    } else {
        echo "   Faça uma transferência IBAN para:\n";
        echo "   {$instructions->getIban()}\n";
        echo "   Titular: {$instructions->getName()}\n";
    }

    echo "   Montante: {$amount} Kz\n";
    echo "   Referência: {$externalRef}\n\n";

    echo "3. Aguardar o comprovativo do cliente...\n";
    echo "   (simulação: user enviou o ficheiro comprovativo.pdf)\n\n";

    $receiptPath = __DIR__ . '/comprovativo.pdf';

    if (!file_exists($receiptPath)) {
        echo "   [!] Crie um PDF chamado 'comprovativo.pdf' na pasta examples/ para testar.\n\n";

        echo "4. Verificar status da sessão sem comprovativo:\n";
        $current = $client->getPaymentSession($session->getId());
        echo "   Status atual: {$current->getStatus()}\n";

        return;
    }

    echo "4. Enviar comprovativo para validação...\n";
    $result = $client->uploadReceipt($session->getId(), $receiptPath);

    if ($result->isValid()) {
        echo "   PAGAMENTO CONFIRMADO!\n";
        echo "   Montante pago: {$result->getAmountPaid()} Kz\n";

        $current = $client->getPaymentSession($session->getId());
        echo "   Status final: {$current->getStatus()}\n";
        echo "   Validado em: {$current->getValidatedAt()}\n";
    } else {
        echo "   Pagamento rejeitado: {$result->getError()}\n";

        foreach ($result->getErrors() as $error) {
            echo "   [{$error['code']}] {$error['message']}\n";
        }
    }
} catch (TaPagoException $e) {
    echo "ERRO: [{$e->getErrorCode()}] {$e->getMessage()}\n";
}
