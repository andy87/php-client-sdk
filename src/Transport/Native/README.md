# Transport Native

Папка содержит HTTP-транспорт без внешних зависимостей.

## NativeHttpTransport

`NativeHttpTransport` выполняет HTTP-запросы средствами PHP stream wrapper: собирает URL с query-string, форматирует headers, отправляет body и возвращает `HttpResponse`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Native\NativeHttpTransport;

$transport = new NativeHttpTransport();

$response = $transport->send(new HttpRequest(
    method: 'GET',
    url: 'https://api.example.com/health',
    headers: ['Accept' => 'application/json'],
    timeout: 10,
));

echo $response->statusCode;
```
