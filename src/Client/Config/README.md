# Client Config

Папка содержит объекты конфигурации, которые задают базовый URL API и поведение клиентского runtime.

## BaseUrl

`BaseUrl` собирает строку базового URL из протокола, хоста, порта и префикса пути. Класс полезен, когда URL нужно хранить структурно, а в provider передавать как строку.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Config\BaseUrl;

// Получится: https://api.example.com/v1
$baseUrl = new BaseUrl(
    host: 'api.example.com',
    protocol: 'https',
    prefix: 'v1',
);

echo (string) $baseUrl;
```

## ClientOptions

`ClientOptions` настраивает timeout, заголовки, события, retry policy, кодировщики, декодер ответа, фабрики запроса и ошибок. Если часть зависимостей не передана, SDK создаёт стандартные реализации.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Client\Event\BeforeRequestEvent;
use and_y87\PhpClientSdk\Client\Event\ClientEvents;
use and_y87\PhpClientSdk\Transport\Retry\DefaultRetryPolicy;

$options = new ClientOptions(
    timeout: 10,
    headers: [
        'User-Agent' => 'ExampleSdk/1.0',
    ],
    events: [
        ClientEvents::BEFORE_REQUEST => static function (BeforeRequestEvent $event): void {
            // Можно добавить диагностический заголовок перед отправкой.
            $event->request->headers['X-Request-Source'] = 'readme-example';
        },
    ],
    retryPolicy: new DefaultRetryPolicy(maxAttempts: 2),
);
```
