# Contracts Auth

Папка содержит интерфейсы авторизации. Их используют provider, resolvers и стратегии авторизации.

## AuthorizationStrategyInterface

`AuthorizationStrategyInterface` добавляет HTTP-заголовки авторизации.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;

final class StaticHeaderStrategy implements AuthorizationStrategyInterface
{
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        // Эти заголовки будут добавлены к приватному запросу.
        return ['Authorization' => 'Bearer token'];
    }
}
```

## AuthorizationQueryStrategyInterface

`AuthorizationQueryStrategyInterface` добавляет авторизационные параметры в query-string.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationQueryStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;

final class QueryKeyStrategy implements AuthorizationQueryStrategyInterface
{
    public function getAuthorizationQueryParameters(HttpTransportInterface $transport): array
    {
        // Параметр будет объединён с query-параметрами Prompt DTO.
        return ['api_key' => 'secret'];
    }
}
```

## RefreshableAuthorizationStrategyInterface

`RefreshableAuthorizationStrategyInterface` описывает стратегию, которую provider может обновить после настраиваемого HTTP-статуса, по умолчанию после `401`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Auth\RefreshableAuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;

final class RefreshableTokenStrategy implements RefreshableAuthorizationStrategyInterface
{
    private string $token = 'old-token';

    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    public function refreshAuthorization(HttpTransportInterface $transport): void
    {
        // Здесь можно запросить новый token через переданный transport.
        $this->token = 'new-token';
    }
}
```

## AuthorizationProfilePromptInterface

`AuthorizationProfilePromptInterface` позволяет Prompt DTO указать логический профиль авторизации.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationProfilePromptInterface;

final class OrdersPrompt implements AuthorizationProfilePromptInterface
{
    public function getAuthorizationProfile(): string
    {
        return 'orders-api';
    }
}
```

## AuthorizationStrategyResolverInterface

`AuthorizationStrategyResolverInterface` выбирает стратегию авторизации для конкретного Prompt DTO. Возврат `null` означает использование стратегии по умолчанию.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyResolverInterface;
use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;

final class SameStrategyResolver implements AuthorizationStrategyResolverInterface
{
    public function resolve(
        PromptInterface $prompt,
        AuthorizationStrategyInterface $defaultStrategy,
    ): ?AuthorizationStrategyInterface {
        // null оставляет defaultStrategy без изменений.
        return null;
    }
}
```
