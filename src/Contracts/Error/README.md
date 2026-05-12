# Contracts Error

Папка содержит контракт фабрики, которая нормализует HTTP-ошибки API.

## ApiErrorFactoryInterface

`ApiErrorFactoryInterface` создаёт `ApiError` из raw HTTP-ответа и декодированного тела.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Error\ApiErrorFactoryInterface;
use and_y87\PhpClientSdk\Response\Error\ApiError;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

final class CustomApiErrorFactory implements ApiErrorFactoryInterface
{
    public function create(HttpResponse $response, array $decodedBody): ApiError
    {
        // Можно адаптировать нестандартный формат ошибки API к общему ApiError.
        return new ApiError(
            raw: $decodedBody,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->body,
        );
    }
}
```
