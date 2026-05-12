# Client Provider

Папка содержит базовый provider для типизированных API-методов.

## AbstractProvider

`AbstractProvider` связывает `PromptInterface`, `ResponseInterface`, стратегию авторизации, HTTP-транспорт, события, retry policy и декодирование ответа. Наследник обычно содержит публичные методы конкретного API и внутри вызывает protected-метод `request()`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Provider\AbstractProvider;
use and_y87\PhpClientSdk\Request\Prompt\PublicPrompt;
use and_y87\PhpClientSdk\Response\Model\AbstractResponse;

/**
 * Описывает запрос получения статуса API.
 */
final class HealthPrompt extends PublicPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/health';
}

/**
 * Описывает ответ статуса API.
 */
final class HealthResponse extends AbstractResponse
{
    protected const FIELD_MAP = ['status' => 'status'];
    protected const REQUIRED_FIELDS = ['status'];

    public string $status;
}

/**
 * Предоставляет типизированные методы health API.
 */
final class HealthProvider extends AbstractProvider
{
    /**
     * Возвращает текущий статус API.
     *
     * @return HealthResponse Типизированный ответ API.
     *
     * @throws InvalidArgumentException Если prompt невалиден.
     * @throws RuntimeException Если транспорт или декодирование завершились ошибкой.
     * @throws UnexpectedValueException Если успешный ответ не содержит обязательные поля.
     */
    public function health(): HealthResponse
    {
        return $this->request(new HealthPrompt(), HealthResponse::class);
    }
}
```
