# Request Prompt

Папка содержит базовые Prompt DTO для описания API-запросов.

## AbstractPrompt

`AbstractPrompt` гидрирует свойства из массива, валидирует обязательные поля, собирает path/query/header/body-параметры и поддерживает `CASTS` для вложенных моделей.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\AbstractPrompt;

final class CreateUserPrompt extends AbstractPrompt
{
    protected const METHOD = 'POST';
    protected const ENDPOINT = '/users';
    protected const FIELD_MAP = [
        'name' => 'name',
        'email' => 'email',
    ];
    protected const REQUIRED_FIELDS = ['name', 'email'];
    protected const BODY_FIELDS = ['name', 'email'];
    protected const CONTENT_TYPE = 'application/json';

    public string $name;
    public string $email;
}

$prompt = new CreateUserPrompt([
    'name' => 'Alice',
    'email' => 'alice@example.com',
]);

$prompt->validate();
$body = $prompt->getBody();
```

## PublicPrompt

`PublicPrompt` используется для endpoints без авторизации. Он наследует всю механику `AbstractPrompt`, но возвращает `requiresAuthorization() === false`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\PublicPrompt;

final class HealthPrompt extends PublicPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/health';
}

$prompt = new HealthPrompt();

var_dump($prompt->requiresAuthorization()); // false
```

## PrivatePrompt

`PrivatePrompt` используется для endpoints с авторизацией и профилем авторизации. Профиль нужен resolver-ам, которые выбирают стратегию по API-разделу.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\PrivatePrompt;

final class OrdersPrompt extends PrivatePrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/orders';
    protected const AUTHORIZATION_PROFILE = 'orders-api';
}

$prompt = new OrdersPrompt();

echo $prompt->getAuthorizationProfile(); // orders-api
```
