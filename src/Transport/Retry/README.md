# Transport Retry

Папка содержит готовые политики повторных HTTP-запросов.

## DefaultRetryPolicy

`DefaultRetryPolicy` повторяет только разрешённые HTTP-методы, по умолчанию `GET`, `HEAD`, `OPTIONS`, при сетевых исключениях или статусах `429`, `500`, `502`, `503`, `504`. Задержка растёт экспоненциально и может учитывать `Retry-After`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Transport\Retry\DefaultRetryPolicy;

$options = new ClientOptions(
    retryPolicy: new DefaultRetryPolicy(
        maxAttempts: 3,
        baseDelayMs: 200,
        maxDelayMs: 2000,
    ),
);
```

## NoRetryPolicy

`NoRetryPolicy` полностью отключает повторы. Это значение используется по умолчанию в `ClientOptions`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Transport\Retry\NoRetryPolicy;

$options = new ClientOptions(
    retryPolicy: new NoRetryPolicy(),
);
```
