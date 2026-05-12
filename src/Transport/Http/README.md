# Transport Http

Папка содержит низкоуровневые HTTP-модели и утилиты.

## HeaderUtils

`HeaderUtils` проверяет, нормализует и объединяет заголовки без учёта регистра имени. Также запрещает CR/LF в имени и значении заголовка.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\HeaderUtils;

$headers = HeaderUtils::merge(
    ['Accept' => 'application/json'],
    ['accept' => 'application/problem+json'],
);

echo $headers['accept']; // application/problem+json
```

## HttpBody

`HttpBody` хранит уже закодированное тело запроса, Content-Type и дополнительные заголовки.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\HttpBody;

$body = new HttpBody(
    content: '{"name":"Alice"}',
    contentType: 'application/json',
);
```

## HttpRequest

`HttpRequest` хранит данные исходящего запроса: метод, URL, headers, query, body, rawBody, timeout и metadata.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\HttpRequest;

$request = new HttpRequest(
    method: 'GET',
    url: 'https://api.example.com/users',
    headers: ['Accept' => 'application/json'],
    query: ['page' => 1],
    timeout: 10,
);
```

## HttpResponse

`HttpResponse` хранит HTTP-статус, заголовки и тело ответа. Метод `json()` декодирует JSON через стандартный `JsonResponseDecoder`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

$response = new HttpResponse(
    statusCode: 200,
    headers: ['Content-Type' => 'application/json'],
    body: '{"ok":true}',
);

$data = $response->json();
```

## MultipartFile

`MultipartFile` описывает локальный файл для `multipart/form-data`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Http\MultipartFile;

$file = new MultipartFile(
    path: '/tmp/avatar.png',
    filename: 'avatar.png',
    contentType: 'image/png',
);
```
