# Contracts Http

Папка содержит контракт транспортного слоя.

## HttpTransportInterface

`HttpTransportInterface` отправляет готовый `HttpRequest` и возвращает `HttpResponse`. Любой сторонний HTTP-клиент можно подключить через этот интерфейс.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

final class FixedTransport implements HttpTransportInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        // Транспорт получает уже собранный URL, headers, query и rawBody.
        return new HttpResponse(
            statusCode: 200,
            headers: ['Content-Type' => 'application/json'],
            body: '{"ok":true}',
        );
    }
}
```
