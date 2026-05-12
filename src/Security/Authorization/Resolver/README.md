# Authorization Resolver

Папка содержит resolver-ы, которые выбирают стратегию авторизации для конкретного Prompt DTO.

## AuthorizationProfileStrategyResolver

`AuthorizationProfileStrategyResolver` выбирает стратегию по профилю из `AuthorizationProfilePromptInterface`, который уже реализует `PrivatePrompt`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\PrivatePrompt;
use and_y87\PhpClientSdk\Security\Authorization\Resolver\AuthorizationProfileStrategyResolver;
use and_y87\PhpClientSdk\Security\Authorization\Strategy\BearerTokenAuthorizationStrategy;

final class OrdersPrompt extends PrivatePrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/orders';
    protected const AUTHORIZATION_PROFILE = 'orders-api';
}

$resolver = new AuthorizationProfileStrategyResolver([
    'orders-api' => new BearerTokenAuthorizationStrategy('orders-token'),
]);
```

## CallbackAuthorizationStrategyResolver

`CallbackAuthorizationStrategyResolver` делегирует выбор стратегии пользовательскому callback.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Resolver\CallbackAuthorizationStrategyResolver;
use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;
use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;

$resolver = new CallbackAuthorizationStrategyResolver(
    static function (
        PromptInterface $prompt,
        AuthorizationStrategyInterface $defaultStrategy,
    ): ?AuthorizationStrategyInterface {
        // Можно вернуть отдельную стратегию для конкретного prompt или null.
        return $prompt->getEndpoint() === '/health'
            ? new NullAuthorizationStrategy()
            : null;
    },
);
```

## PromptClassAuthorizationStrategyResolver

`PromptClassAuthorizationStrategyResolver` выбирает стратегию по точному классу Prompt DTO.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\PrivatePrompt;
use and_y87\PhpClientSdk\Security\Authorization\Resolver\PromptClassAuthorizationStrategyResolver;
use and_y87\PhpClientSdk\Security\Authorization\Strategy\ApiKeyAuthorizationStrategy;

final class ProfilePrompt extends PrivatePrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/profile';
}

$resolver = new PromptClassAuthorizationStrategyResolver([
    ProfilePrompt::class => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
]);
```
