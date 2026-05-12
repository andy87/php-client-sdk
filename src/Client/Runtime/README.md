# Client Runtime

Папка содержит runtime-контекст клиента: обработчики событий и пользовательские HTTP-заголовки.

## ClientRuntime

`ClientRuntime` регистрирует listeners, вызывает события и объединяет заголовки без учёта регистра имени.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\ClientEvents;
use and_y87\PhpClientSdk\Client\Runtime\ClientRuntime;

$runtime = new ClientRuntime(headers: [
    'User-Agent' => 'ExampleSdk/1.0',
]);

$runtime->on(ClientEvents::BEFORE_REQUEST, static function (object $event): void {
    // Listener получит DTO события, переданный через dispatch().
});

$runtime->addHeaders([
    'Accept-Language' => 'ru',
]);

$headers = $runtime->mergeHeaders(
    ['Accept' => 'application/json'],
    $runtime->getHeaders(),
);
```
