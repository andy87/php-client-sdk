# Contracts Retry

Папка содержит контракт политики повторных HTTP-запросов.

## RetryPolicyInterface

`RetryPolicyInterface` решает, нужно ли повторять запрос после ответа или исключения, и возвращает задержку перед следующей попыткой.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Retry\RetryPolicyInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

final class RetryOnceOnServerError implements RetryPolicyInterface
{
    public function shouldRetry(
        int $attempt,
        HttpRequest $request,
        ?HttpResponse $response = null,
        ?Throwable $exception = null,
    ): bool {
        // Повторяем только один раз и только 5xx-ответы.
        return $attempt === 1
            && $response !== null
            && $response->statusCode >= 500;
    }

    public function getDelayMs(
        int $attempt,
        HttpRequest $request,
        ?HttpResponse $response = null,
        ?Throwable $exception = null,
    ): int {
        return 100;
    }
}
```
