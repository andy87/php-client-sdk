# Response Model

Папка содержит базовую модель типизированного ответа API.

## AbstractResponse

`AbstractResponse` гидрирует публичные свойства по `FIELD_MAP`, хранит HTTP-статус, заголовки, raw body, decoded body, исходный request и `ApiError`. На успешных ответах проверяет `REQUIRED_FIELDS`, если включена strict validation.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Response\Model\AbstractResponse;

final class UserResponse extends AbstractResponse
{
    protected const FIELD_MAP = [
        'id' => 'id',
        'name' => 'name',
    ];
    protected const REQUIRED_FIELDS = ['id', 'name'];

    public int $id;
    public string $name;
}

$response = new UserResponse(
    data: ['id' => 7, 'name' => 'Alice'],
    statusCode: 200,
    headers: ['Content-Type' => 'application/json'],
    rawBody: '{"id":7,"name":"Alice"}',
);

echo $response->id;
echo $response->getStatusCode();
```
