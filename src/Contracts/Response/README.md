# Contracts Response

Папка содержит контракты response-слоя: декодер raw HTTP-ответа и типизированный DTO ответа.

## ResponseDecoderInterface

`ResponseDecoderInterface` декодирует `HttpResponse` в массив данных для response DTO.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Response\ResponseDecoderInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

final class EmptyResponseDecoder implements ResponseDecoderInterface
{
    public function decode(HttpResponse $response): array
    {
        // Подходит для API, где тело ответа не используется.
        return [];
    }
}
```

## ResponseInterface

`ResponseInterface` описывает единый доступ к ошибке, статусу, заголовкам, raw body, декодированному body и исходному `HttpRequest`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Response\Model\AbstractResponse;

final class StatusResponse extends AbstractResponse
{
    protected const FIELD_MAP = ['status' => 'status'];
    protected const REQUIRED_FIELDS = ['status'];

    public string $status;
}

$response = new StatusResponse(['status' => 'ok']);

echo $response->getStatusCode(); // 0, если статус не передавался в конструктор.
echo $response->toArray()['status'];
```
