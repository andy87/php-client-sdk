# Response Decoder

Папка содержит декодеры raw HTTP-ответов.

## JsonResponseDecoder

`JsonResponseDecoder` декодирует JSON-ответы в массив. Пустое тело возвращает `[]`. Некорректный JSON в успешном ответе приводит к `ResponseDecodeException`; для HTTP-ошибок декодер возвращает пустой массив, если тело не удалось разобрать.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Response\Decoder\JsonResponseDecoder;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

$response = new HttpResponse(
    statusCode: 200,
    headers: ['Content-Type' => 'application/json'],
    body: '{"id":7,"name":"Alice"}',
);

$data = (new JsonResponseDecoder())->decode($response);

echo $data['name']; // Alice
```
