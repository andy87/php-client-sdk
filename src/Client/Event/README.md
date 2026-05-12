# Client Event

Папка содержит DTO событий и список имён событий, которые используются `ClientRuntime` и `AbstractProvider`.

## ClientEvents

`ClientEvents` хранит константы имён событий: инициализация клиента, запрос перед отправкой, успешный ответ и ошибка запроса.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\ClientEvents;
use and_y87\PhpClientSdk\Client\Runtime\ClientRuntime;

$runtime = new ClientRuntime();

$runtime->on(ClientEvents::BEFORE_REQUEST, static function (object $event): void {
    // Общий обработчик события перед HTTP-запросом.
});
```

## AfterInitEvent

`AfterInitEvent` передаёт созданный клиент в обработчики, которым нужно выполнить post-init настройку.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\AfterInitEvent;

$event = new AfterInitEvent(client: new stdClass());

// В обработчике можно получить ссылку на инициализированный клиент.
$client = $event->client;
```

## BeforeRequestEvent

`BeforeRequestEvent` вызывается перед отправкой запроса. `HttpRequest` остаётся mutable, поэтому обработчик может добавить заголовки, query-параметры или metadata.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\BeforeRequestEvent;

/** @var BeforeRequestEvent $event */
$event->request->headers['X-Debug'] = '1';
$event->request->metadata['startedBy'] = 'event-listener';
```

## AfterRequestEvent

`AfterRequestEvent` вызывается после получения HTTP-ответа и создания типизированного response DTO.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\AfterRequestEvent;

/** @var AfterRequestEvent $event */
$statusCode = $event->httpResponse->statusCode;
$typedResponse = $event->response;
```

## RequestExceptionEvent

`RequestExceptionEvent` вызывается, когда выполнение запроса завершилось исключением. Исключение не подавляется и будет проброшено наружу.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\RequestExceptionEvent;

/** @var RequestExceptionEvent $event */
$message = $event->exception->getMessage();
$requestUrl = $event->request?->url;
```
