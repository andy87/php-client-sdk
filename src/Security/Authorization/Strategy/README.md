# Authorization Strategy

Папка содержит готовые стратегии авторизации.

## ApiKeyAuthorizationStrategy

`ApiKeyAuthorizationStrategy` добавляет API key в header или query-параметр.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\ApiKeyAuthorizationStrategy;

$headerStrategy = new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret');

$queryStrategy = new ApiKeyAuthorizationStrategy(
    name: 'api_key',
    value: 'secret',
    location: ApiKeyAuthorizationStrategy::LOCATION_QUERY,
);
```

## BasicAuthorizationStrategy

`BasicAuthorizationStrategy` добавляет HTTP Basic Authorization.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\BasicAuthorizationStrategy;

$strategy = new BasicAuthorizationStrategy(
    username: 'client',
    password: 'secret',
);
```

## BearerTokenAuthorizationStrategy

`BearerTokenAuthorizationStrategy` добавляет статический Bearer token.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\BearerTokenAuthorizationStrategy;

$strategy = new BearerTokenAuthorizationStrategy('access-token');
```

## CallbackAuthorizationStrategy

`CallbackAuthorizationStrategy` получает заголовки авторизации из callback. Подходит, когда token хранится во внешнем сервисе или обновляется вне SDK.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\CallbackAuthorizationStrategy;

$strategy = new CallbackAuthorizationStrategy(
    static fn(): array => ['Authorization' => 'Bearer dynamic-token'],
);
```

## ClientCredentialsAuthorizationStrategy

`ClientCredentialsAuthorizationStrategy` выполняет OAuth `client_credentials`, кеширует access token и умеет принудительно обновляться.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\ClientCredentialsAuthorizationStrategy;
use and_y87\PhpClientSdk\Transport\Cache\ArrayCache;

$strategy = new ClientCredentialsAuthorizationStrategy(
    tokenUrl: 'https://auth.example.com/oauth/token',
    clientId: 'client-id',
    clientSecret: 'client-secret',
    scope: 'users:read',
    tokenCache: new ArrayCache(),
);
```

## NullAuthorizationStrategy

`NullAuthorizationStrategy` возвращает пустые заголовки и используется для публичных API.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;

$strategy = new NullAuthorizationStrategy();
```
