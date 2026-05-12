# Exception

Папка содержит типизированные исключения SDK. Их удобно ловить отдельно от общих PHP-исключений.

## AuthorizationException

`AuthorizationException` используется при ошибке получения или обновления авторизации.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Exception\AuthorizationException;

try {
    throw new AuthorizationException('OAuth token was not received.');
} catch (AuthorizationException $exception) {
    // Ошибка относится именно к авторизации.
    echo $exception->getMessage();
}
```

## ResponseDecodeException

`ResponseDecodeException` используется, когда успешный HTTP-ответ нельзя декодировать в ожидаемый формат.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Exception\ResponseDecodeException;

try {
    throw new ResponseDecodeException('API returned invalid JSON response.');
} catch (ResponseDecodeException $exception) {
    // Ошибка относится к raw body ответа.
}
```

## ResponseHydrationException

`ResponseHydrationException` используется, когда response DTO не удалось создать из декодированных данных.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Exception\ResponseHydrationException;

try {
    throw new ResponseHydrationException('Response DTO hydration failed.');
} catch (ResponseHydrationException $exception) {
    // Ошибка относится к маппингу данных в DTO.
}
```

## TransportException

`TransportException` используется при ошибке HTTP-транспорта.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Exception\TransportException;

try {
    throw new TransportException('HTTP request failed.');
} catch (TransportException $exception) {
    // Ошибка относится к сетевому или транспортному слою.
}
```

## ValidationException

`ValidationException` используется при невалидном Prompt DTO или собранном HTTP-запросе.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Exception\ValidationException;

try {
    throw new ValidationException('Endpoint path parameter "{id}" is not filled.');
} catch (ValidationException $exception) {
    // Ошибка относится к входным данным запроса.
}
```
