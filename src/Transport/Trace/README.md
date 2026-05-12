# Transport Trace

Папка содержит диагностическую обёртку транспорта и записи HTTP-вызовов.

## TraceableTransport

`TraceableTransport` оборачивает любой `HttpTransportInterface`, сохраняет запрос, ответ или исключение и длительность каждого вызова.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\CallbackMockResponseResolver;
use and_y87\PhpClientSdk\Testing\Mock\MockTransport;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;
use and_y87\PhpClientSdk\Transport\Trace\TraceableTransport;

$transport = new TraceableTransport(new MockTransport(
    new CallbackMockResponseResolver(
        static fn(HttpRequest $request): HttpResponse => new HttpResponse(200, [], '{}'),
    ),
));

$transport->send(new HttpRequest('GET', 'https://api.example.com/health'));

$lastRecord = $transport->getLastRecord();
echo $lastRecord?->durationMs;
```

## HttpTraceRecord

`HttpTraceRecord` хранит один результат вызова транспорта: request, response, exception и длительность.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;
use and_y87\PhpClientSdk\Transport\Trace\HttpTraceRecord;

$record = new HttpTraceRecord(
    request: new HttpRequest('GET', 'https://api.example.com/health'),
    response: new HttpResponse(200, [], '{}'),
    exception: null,
    durationMs: 12.5,
);
```
