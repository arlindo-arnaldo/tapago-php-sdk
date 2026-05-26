# TaPago SDK PHP

SDK oficial para integrar a [TaPago API](https://tapago.app) no seu projeto PHP.

## Requisitos

- PHP ^8.0
- Guzzle ^7.0

## Instalação

```bash
composer require tapago/sdk
```

## Configuração

Obtenha o seu token de API na página de configurações da sua conta TaPago.

```php
use TaPago\TaPagoClient;

$client = new TaPagoClient('seu-token-aqui');
```

Se precisar de apontar para um URL diferente (ex: ambiente de desenvolvimento):

```php
$client = new TaPagoClient(
    'seu-token-aqui',
    'http://localhost:8000/api'
);
```

## Uso

### Criar sessão de pagamento

```php
$session = $client->createPaymentSession('pedido-123', 5000);

echo $session->getId();                          // uuid
echo $session->getStatus();                      // 'pending'
echo $session->getAmount();                      // 5000
echo $session->getExternalRef();                 // 'pedido-123'

$instructions = $session->getPaymentInstructions();
echo $instructions->getType();                   // 'express' | 'iban'
echo $instructions->getNumber();                 // número Multicaixa Express
echo $instructions->getIban();                   // IBAN (se aplicável)
```

### Listar sessões

```php
$list = $client->listPaymentSessions();

foreach ($list->getData() as $session) {
    echo "[{$session->getStatus()}] {$session->getId()}\n";
}

// Com filtro
$completed = $client->listPaymentSessions(status: 'completed');

echo $list->getTotal();                          // total de registos
echo $list->getCurrentPage();                    // página atual
echo $list->hasMorePages();                      // bool
```

### Obter sessão

```php
$session = $client->getPaymentSession('uuid-da-sessao');

echo $session->getStatus();                      // 'completed'
echo $session->isValid();                        // true
echo $session->getAmountPaid();                  // 5000
```

### Validar comprovativo

```php
$result = $client->uploadReceipt('uuid-da-sessao', '/caminho/comprovativo.pdf');

if ($result->isValid()) {
    echo "Pagamento confirmado: {$result->getAmountPaid()} Kz";
} else {
    echo "Rejeitado: {$result->getError()}";
    print_r($result->getErrorCodes());           // ['AMOUNT_MISMATCH', ...]
}
```

## Tratamento de erros

A SDK lança excepções específicas para cada tipo de erro:

| Excepção | Código | Quando |
|----------|--------|--------|
| `ApiNotConfiguredException` | 403 | API não ativada ou método de pagamento não configurado |
| `SessionNotFoundException` | 404 | Sessão de pagamento não encontrada |
| `DuplicateExternalRefException` | 409 | `external_ref` duplicado |
| `SessionAlreadyProcessedException` | 409 | Tentativa de reenviar comprovativo para sessão já processada |
| `InsufficientCreditsException` | 402 | Créditos insuficientes |
| `ValidationFailedException` | 422 | Erros de validação nos parâmetros |
| `RateLimitExceededException` | 429 | Limite de 30 req/min excedido |
| `TaPagoException` | — | Erro genérico ou desconhecido |

### Exemplo

```php
use TaPago\Exceptions\ApiNotConfiguredException;
use TaPago\Exceptions\DuplicateExternalRefException;
use TaPago\Exceptions\InsufficientCreditsException;
use TaPago\Exceptions\SessionNotFoundException;
use TaPago\Exceptions\TaPagoException;
use TaPago\Exceptions\ValidationFailedException;

try {
    $session = $client->createPaymentSession('pedido-123', 5000);
} catch (DuplicateExternalRefException $e) {
    echo "Referência duplicada.";
} catch (ValidationFailedException $e) {
    print_r($e->getValidationErrors());
} catch (TaPagoException $e) {
    echo "Erro: [{$e->getErrorCode()}] {$e->getMessage()}";
}
```

Para erros de negócio na validação de comprovativos (ex: `AMOUNT_MISMATCH`, `RECIPIENT_MISMATCH`), a SDK **não lança excepções** — o resultado é retornado via `ReceiptValidationResult` com `isValid()` = `false`. Verifique `$result->getErrorCodes()` para obter os códigos de erro.

## Testes

```bash
composer install
vendor/bin/phpunit
```

## Exemplos

Veja a pasta `examples/` para scripts completos:

- `01-create-session.php` — criar sessão
- `02-list-sessions.php` — listar e filtrar
- `03-upload-receipt.php` — upload de comprovativo
- `04-full-flow.php` — fluxo completo

## Licença

MIT
