# Contracts Request

Папка содержит контракты request-слоя: DTO запроса, фабрика HTTP-запроса и финализатор.

## PromptInterface

`PromptInterface` описывает API-запрос: метод, endpoint, path/query/header/body-параметры, Content-Type и необходимость авторизации.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;

final class PingPrompt implements PromptInterface
{
    public function getMethod(): string { return 'GET'; }
    public function getEndpoint(): string { return '/ping'; }
    public function getContentType(): ?string { return null; }
    public function requiresAuthorization(): bool { return false; }
    public function getPathParameters(): array { return []; }
    public function getQueryParameters(): array { return ['verbose' => true]; }
    public function getHeaderParameters(): array { return []; }
    public function getBody(): array|string|null { return null; }

    public function validate(): void
    {
        // Проверка обязательных полей, если они есть.
    }
}
```

## RequestFactoryInterface

`RequestFactoryInterface` создаёт `HttpRequest` из Prompt DTO, базового URL, заголовков, timeout и дополнительных query-параметров.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;
use and_y87\PhpClientSdk\Contracts\Request\RequestFactoryInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;

final class MinimalRequestFactory implements RequestFactoryInterface
{
    public function create(
        PromptInterface $prompt,
        string $baseUrl,
        array $headers,
        int $timeout,
        array $extraQuery = [],
    ): HttpRequest {
        return new HttpRequest(
            method: $prompt->getMethod(),
            url: rtrim($baseUrl, '/') . '/' . ltrim($prompt->getEndpoint(), '/'),
            headers: $headers,
            query: array_merge($prompt->getQueryParameters(), $extraQuery),
            timeout: $timeout,
        );
    }
}
```

## RequestFinalizerInterface

`RequestFinalizerInterface` пересчитывает производные данные запроса после пользовательских событий и перед транспортом.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Request\RequestFinalizerInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;

final class MetadataFinalizer implements RequestFinalizerInterface
{
    public function finalize(HttpRequest $request): HttpRequest
    {
        // Пример служебной metadata перед отправкой.
        $request->metadata['finalized'] = true;

        return $request;
    }
}
```
