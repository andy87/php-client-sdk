# Testing Mock

Папка содержит mock-транспорт и resolver-ы ответов для тестов без сетевых запросов.

## MockResponseResolverInterface

`MockResponseResolverInterface` описывает объект, который подбирает `HttpResponse` по данным `HttpRequest`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\MockResponseResolverInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

final class AlwaysOkResolver implements MockResponseResolverInterface
{
    public function resolve(HttpRequest $request): ?HttpResponse
    {
        // Возвращает один и тот же ответ для любого запроса.
        return new HttpResponse(200, ['Content-Type' => 'application/json'], '{"ok":true}');
    }
}
```

## MockTransport

`MockTransport` реализует `HttpTransportInterface` и возвращает ответы из resolver-а. Если ответ не найден, выбрасывается `TransportException`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\MockTransport;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;

$transport = new MockTransport(new AlwaysOkResolver());

$response = $transport->send(new HttpRequest('GET', 'https://api.example.com/ping'));
```

## CallbackMockResponseResolver

`CallbackMockResponseResolver` выбирает mock-ответ через callback.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\CallbackMockResponseResolver;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

$resolver = new CallbackMockResponseResolver(
    static function (HttpRequest $request): ?HttpResponse {
        if ($request->url === 'https://api.example.com/ping') {
            return new HttpResponse(200, ['Content-Type' => 'application/json'], '{"ok":true}');
        }

        return null;
    },
);
```

## CompositeMockResponseResolver

`CompositeMockResponseResolver` перебирает несколько resolver-ов и возвращает первый найденный ответ.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\CallbackMockResponseResolver;
use and_y87\PhpClientSdk\Testing\Mock\CompositeMockResponseResolver;
use and_y87\PhpClientSdk\Testing\Mock\RouteMockResponseResolver;

$resolver = new CompositeMockResponseResolver([
    new RouteMockResponseResolver(),
    new CallbackMockResponseResolver(static fn(): null => null),
]);
```

## PromptClassMockResponseResolver

`PromptClassMockResponseResolver` выбирает ответ по классу Prompt DTO из `HttpRequest::$metadata['promptClass']`. Эта metadata добавляется `DefaultRequestFactory`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\PublicPrompt;
use and_y87\PhpClientSdk\Testing\Mock\PromptClassMockResponseResolver;

final class HealthPrompt extends PublicPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/health';
}

$resolver = (new PromptClassMockResponseResolver())->addJson(
    promptClass: HealthPrompt::class,
    body: ['status' => 'ok'],
);
```

## RouteMockResponseResolver

`RouteMockResponseResolver` выбирает ответ по HTTP-методу и абсолютному URL, path или endpoint-шаблону.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\RouteMockResponseResolver;

$resolver = (new RouteMockResponseResolver())->addJson(
    method: 'GET',
    pathOrUrl: '/health',
    body: ['status' => 'ok'],
);
```
