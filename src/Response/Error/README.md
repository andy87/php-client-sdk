# Response Error

Папка содержит модель нормализованной API-ошибки и фабрику её создания.

## ApiError

`ApiError` хранит код, HTTP-статус, сообщение, тип, дополнительные детали, исходное тело и заголовки ответа. Класс понимает как плоский формат ошибки, так и формат с ключом `error`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Response\Error\ApiError;

$error = new ApiError(
    raw: [
        'error' => [
            'code' => 'invalid_request',
            'message' => 'Field email is required.',
            'type' => 'validation',
        ],
    ],
    statusCode: 422,
);

echo $error->code; // invalid_request
echo $error->message; // Field email is required.
```

## DefaultApiErrorFactory

`DefaultApiErrorFactory` создаёт `ApiError` из `HttpResponse` и декодированного тела ответа.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Response\Error\DefaultApiErrorFactory;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

$httpResponse = new HttpResponse(
    statusCode: 404,
    headers: ['Content-Type' => 'application/json'],
    body: '{"message":"Not found"}',
);

$apiError = (new DefaultApiErrorFactory())->create(
    response: $httpResponse,
    decodedBody: ['message' => 'Not found'],
);

echo $apiError->statusCode; // 404
```
