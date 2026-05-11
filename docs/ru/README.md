# PHP Client SDK

Базовые абстракции для создания типизированных PHP API-клиентов.

[English documentation](../../README.md)

## Обзор

`andy87/php-client-sdk` предоставляет небольшой набор переиспользуемых компонентов для SDK API-клиентов:

- prompt DTO для HTTP-метода, endpoint, path-параметров, query-параметров, тела запроса и валидации;
- response DTO для нормализованных данных ответа, HTTP-статуса, заголовков и ошибок API;
- базовый provider для выполнения типизированных API-методов;
- подключаемые стратегии авторизации;
- подключаемый HTTP-транспорт с нативной реализацией через PHP stream wrapper.

Пакет не генерирует API-клиенты и не привязан к конкретной HTTP-библиотеке.

## Требования

- PHP 8.1 или выше.
- Composer.

## Установка

```bash
composer require andy87/php-client-sdk
```

## Основные понятия

Пакет разделяет API-вызов на три части:

- `PromptInterface` описывает исходящий запрос.
- `ResponseInterface` описывает типизированный ответ API.
- `AbstractProvider` связывает prompt, response, авторизацию и HTTP-транспорт.

`NativeHttpTransport` можно использовать без дополнительных зависимостей. Если проекту нужен другой транспорт, реализуйте `HttpTransportInterface`.

## Prompt DTO

Наследуйте `AbstractPrompt`, чтобы описать запрос. Базовый класс заполняет объявленные свойства из входных данных, проверяет обязательные поля, собирает path/query/body-массивы и нормализует вложенные DTO через `toArray()` или `toValue()`, если такие методы существуют.

Используйте `PublicPrompt` для публичных endpoints и `PrivatePrompt` для приватных endpoints с профилем авторизации. `AbstractPrompt` остаётся универсальным базовым классом для пользовательских схем prompt.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\AbstractPrompt;

/**
 * Описывает запрос получения одного пользователя по идентификатору.
 */
final class GetUserPrompt extends AbstractPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/users/{id}';
    protected const FIELD_MAP = [
        'id' => 'id',
        'includePosts' => 'include_posts',
    ];
    protected const REQUIRED_FIELDS = ['id'];
    protected const PATH_FIELDS = ['id'];
    protected const QUERY_FIELDS = ['includePosts'];
    protected const BODY_FIELDS = [];
    protected const CONTENT_TYPE = null;

    public int $id;
    public ?bool $includePosts = null;
}
```

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Prompt\PrivatePrompt;
use and_y87\PhpClientSdk\Request\Prompt\PublicPrompt;

/**
 * Описывает публичный health-check запрос.
 */
final class HealthPrompt extends PublicPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/health';
}

/**
 * Описывает приватный запрос создания заказа.
 */
final class CreateOrderPrompt extends PrivatePrompt
{
    protected const METHOD = 'POST';
    protected const ENDPOINT = '/orders';
    protected const AUTHORIZATION_PROFILE = 'orders-api';
}
```

## Response DTO

Наследуйте `AbstractResponse`, чтобы описать данные, которые возвращает API. При успешном ответе базовый класс заполняет свойства из `FIELD_MAP` и проверяет `REQUIRED_FIELDS`. При HTTP-ошибке он сохраняет `ApiError` и пропускает проверку обязательных полей.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Response\Model\AbstractResponse;

/**
 * Хранит данные пользователя, возвращенные API.
 */
final class GetUserResponse extends AbstractResponse
{
    protected const FIELD_MAP = [
        'id' => 'id',
        'name' => 'name',
    ];
    protected const REQUIRED_FIELDS = ['id', 'name'];

    public int $id;
    public string $name;
}
```

## Использование provider

Наследуйте `AbstractProvider` и добавляйте публичные методы для конкретных API-операций. Защищенный метод `request()` валидирует prompt, добавляет заголовки авторизации, если они нужны, отправляет HTTP-запрос и возвращает указанный response DTO.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Provider\AbstractProvider;

/**
 * Предоставляет типизированный доступ к API-методам пользователей.
 */
final class UsersProvider extends AbstractProvider
{
    /**
     * Загружает одного пользователя по идентификатору.
     *
     * @param int $id Идентификатор пользователя.
     *
     * @return GetUserResponse Типизированный ответ API.
     *
     * @throws InvalidArgumentException Если prompt не прошел валидацию.
     * @throws RuntimeException Если авторизация или транспорт завершились ошибкой.
     * @throws UnexpectedValueException Если в успешном ответе нет обязательных полей.
     */
    public function getUser(int $id): GetUserResponse
    {
        return $this->request(
            new GetUserPrompt(['id' => $id]),
            GetUserResponse::class,
        );
    }
}
```

Создайте provider с базовым URL, стратегией авторизации и транспортом:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;
use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Transport\Native\NativeHttpTransport;
use and_y87\PhpClientSdk\Transport\Retry\DefaultRetryPolicy;

$provider = new UsersProvider(
    baseUrl: 'https://api.example.com',
    authorizationStrategy: new NullAuthorizationStrategy(),
    transport: new NativeHttpTransport(),
    options: new ClientOptions(
        timeout: 30,
        retryPolicy: new DefaultRetryPolicy(maxAttempts: 2),
    ),
);

$response = $provider->getUser(123);

if ($response->hasError()) {
    $error = $response->getError();
    echo $error?->message ?? 'API request failed.';
}

echo $response->getStatusCode();
```

## Настройки клиента

`ClientOptions` — основная точка расширения SDK. Если объект не передан, SDK использует безопасные настройки по умолчанию: JSON-запросы и ответы, строгую проверку успешных ответов, отключённые повторы и стандартную фабрику запросов.

Настраиваемые части:

- `timeout`, `headers`, `events`;
- `strictValidation`;
- `validatePrompt`;
- `retryPolicy`;
- `queryEncoder`;
- `bodyEncoder`;
- `responseDecoder`;
- `errorFactory`;
- `requestFactory`.
- `authorizationResolver`;
- `refreshAuthorizationStatusCodes`.

Повторы запросов выключены по умолчанию. Используйте `DefaultRetryPolicy` только для API-операций, где повтор безопасен.

`validatePrompt` управляет локальной валидацией prompt перед сборкой запроса. По умолчанию опция включена. Устанавливайте `false` только в mock или test окружениях, где клиент должен возвращать успешные fixture-ответы даже при неполном входе:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Config\ClientOptions;

$options = new ClientOptions(
    strictValidation: true,
    validatePrompt: false,
);
```

`refreshAuthorizationStatusCodes` по умолчанию равен `[401]`. Если выбранная стратегия авторизации реализует `RefreshableAuthorizationStrategyInterface`, provider обновляет авторизацию и один раз повторяет запрос после этих статусов. Передайте пустой список, чтобы отключить это поведение.

Используйте `BaseUrl`, когда клиенту нужно отдельно настроить protocol, host, port и prefix:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Config\BaseUrl;

$baseUrl = new BaseUrl(
    host: 'api.example.com',
    protocol: 'https',
    prefix: 'api/v1',
);
```

## Runtime-события и заголовки

`ClientRuntime` хранит дефолтные заголовки запросов и обработчики событий, общие для клиента и его provider-ов. Передавайте один runtime-объект во все provider-ы, которым нужны общие заголовки и listeners.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Client\Event\BeforeRequestEvent;
use and_y87\PhpClientSdk\Client\Event\ClientEvents;
use and_y87\PhpClientSdk\Client\Runtime\ClientRuntime;

$runtime = new ClientRuntime(
    headers: [
        'X-Client' => 'crm',
    ],
    events: [
        ClientEvents::BEFORE_REQUEST => static function (BeforeRequestEvent $event): void {
            $event->request->headers['X-Trace-Id'] = bin2hex(random_bytes(8));
        },
    ],
);

$runtime->addHeaders([
    'X-Account' => 'main',
]);
```

Поддерживаемые события:

- `ClientEvents::AFTER_INIT` после завершения инициализации конкретного клиента.
- `ClientEvents::BEFORE_REQUEST` перед отправкой mutable `HttpRequest` транспортом.
- `ClientEvents::AFTER_REQUEST` после преобразования raw HTTP-ответа в типизированный response DTO.
- `ClientEvents::REQUEST_EXCEPTION` после ошибки транспорта, JSON-декодирования или создания response DTO.

Имена заголовков объединяются без учёта регистра. Заголовки авторизации перекрывают дефолтные runtime-заголовки, а listeners `BEFORE_REQUEST` могут изменить уже финальный запрос.

## Авторизация

Используйте `NullAuthorizationStrategy` для публичных API:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;

$authorization = new NullAuthorizationStrategy();
```

Используйте `ClientCredentialsAuthorizationStrategy` для OAuth `client_credentials`. Стратегия запрашивает access token через настроенный транспорт и кеширует его до истечения срока действия. По умолчанию токен хранится в памяти процесса.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\ClientCredentialsAuthorizationStrategy;

$authorization = new ClientCredentialsAuthorizationStrategy(
    tokenUrl: 'https://auth.example.com/oauth/token',
    clientId: 'client-id',
    clientSecret: 'client-secret',
    scope: 'users.read',
    timeout: 30,
);
```

Передайте `CacheInterface`, если токен должен переживать текущий PHP-процесс. SDK поставляет `ArrayCache` для memory-сценариев и `SimpleCacheAdapter` для подключения PSR-16/simple-cache совместимых хранилищ без прямой зависимости пакета от конкретного фреймворка.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\ClientCredentialsAuthorizationStrategy;
use and_y87\PhpClientSdk\Transport\Cache\SimpleCacheAdapter;

$authorization = new ClientCredentialsAuthorizationStrategy(
    tokenUrl: 'https://auth.example.com/oauth/token',
    clientId: 'client-id',
    clientSecret: 'client-secret',
    tokenCache: new SimpleCacheAdapter($psr16Cache),
    tokenCacheKey: 'oauth:example:client-id',
    clockSkew: 60,
);
```

Во внешний кеш сохраняется массив с `access_token` и `expires_at`. `clockSkew` задаёт раннее обновление токена: при значении `60` стратегия перестанет использовать токен за 60 секунд до `expires_at`.

`ClientCredentialsAuthorizationStrategy` обновляет cached token, если provider получил настроенный refresh status, по умолчанию `401`, и после этого provider один раз повторяет исходный запрос.

Другие встроенные стратегии:

- `BearerTokenAuthorizationStrategy` для статического Bearer token;
- `BasicAuthorizationStrategy` для HTTP Basic auth;
- `ApiKeyAuthorizationStrategy` для API key в header или query;
- `CallbackAuthorizationStrategy` для проектной логики авторизационных заголовков.

По умолчанию prompt требует авторизацию. Переопределите константу prompt, если запрос публичный:

```php
protected const AUTHORIZATION_REQUIRED = false;
```

Используйте authorization resolver, если разным операциям нужны разные стратегии авторизации:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\ApiKeyAuthorizationStrategy;
use and_y87\PhpClientSdk\Security\Authorization\Resolver\AuthorizationProfileStrategyResolver;
use and_y87\PhpClientSdk\Security\Authorization\Resolver\PromptClassAuthorizationStrategyResolver;
use and_y87\PhpClientSdk\Client\Config\ClientOptions;

$options = new ClientOptions(
    authorizationResolver: new PromptClassAuthorizationStrategyResolver([
        GetUserPrompt::class => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
    ]),
);
```

Для наследников `PrivatePrompt` предпочитайте имена профилей вроде `default`, `avito-client-credentials`, `api-key` или `sandbox-token`:

```php
$options = new ClientOptions(
    authorizationResolver: new AuthorizationProfileStrategyResolver([
        'orders-api' => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
    ]),
);
```

## HTTP-транспорт

`NativeHttpTransport` отправляет запросы через PHP stream wrapper. Он поддерживает:

- query-параметры;
- JSON-тела запросов;
- тела запросов `application/x-www-form-urlencoded`;
- тела запросов `multipart/form-data` через `MultipartFile`;
- заранее закодированные raw-тела запросов;
- HTTP-статус и заголовки ответа;
- декодирование JSON-ответа через `HttpResponse::json()`.

Пользовательский транспорт должен реализовать `HttpTransportInterface`:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

/**
 * Отправляет HTTP-запросы через клиент приложения.
 */
final class CustomTransport implements HttpTransportInterface
{
    /**
     * Отправляет HTTP-запрос.
     *
     * @param HttpRequest $request Данные запроса.
     *
     * @return HttpResponse Данные ответа.
     *
     * @throws RuntimeException Если запрос невозможно отправить.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        throw new RuntimeException('Implement transport integration here.');
    }
}
```

## Mock-транспорт

`MockTransport` возвращает настроенные fixture-ответы `HttpResponse` и никогда не переключается на реальные сетевые запросы. Используйте его для тестовых стендов, где клиент должен возвращать успешные данные в формате API без обращения к внешнему сервису.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;
use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Testing\Mock\MockTransport;
use and_y87\PhpClientSdk\Testing\Mock\PromptClassMockResponseResolver;
use and_y87\PhpClientSdk\Testing\Mock\RouteMockResponseResolver;

$resolver = (new RouteMockResponseResolver())
    ->addJson('GET', '/users/{id}', [
        'id' => 123,
        'name' => 'Mock User',
    ]);

$provider = new UsersProvider(
    baseUrl: 'https://api.example.com',
    authorizationStrategy: new NullAuthorizationStrategy(),
    transport: new MockTransport($resolver),
    options: new ClientOptions(validatePrompt: false),
);
```

Route сопоставляется по HTTP-методу и абсолютному URL, path или endpoint-шаблону из metadata запроса. OAuth token request можно замокать по абсолютному token URL:

```php
$resolver->addJson('POST', 'https://auth.example.com/oauth/token', [
    'access_token' => 'mock-token',
    'expires_in' => 3600,
]);
```

`validatePrompt=false` отключает только `Prompt::validate()`. Сборка запроса всё ещё может упасть, если prompt не может вернуть method, endpoint или обязательный path-плейсхолдер.

Если route нестабилен или сгенерирован, используйте `PromptClassMockResponseResolver`, чтобы привязать fixture к классу Prompt DTO:

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Testing\Mock\MockTransport;
use and_y87\PhpClientSdk\Testing\Mock\PromptClassMockResponseResolver;

$resolver = (new PromptClassMockResponseResolver())
    ->addJson(GetUserPrompt::class, [
        'id' => 123,
        'name' => 'Mock User',
    ]);

$provider = new UsersProvider(
    baseUrl: 'https://api.example.com',
    authorizationStrategy: new NullAuthorizationStrategy(),
    transport: new MockTransport($resolver),
);
```

## Traceable transport

`TraceableTransport` оборачивает любой `HttpTransportInterface` и записывает запросы, ответы, исключения и длительность без изменения поведения транспорта.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Native\NativeHttpTransport;
use and_y87\PhpClientSdk\Transport\Trace\TraceableTransport;
use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;

$transport = new TraceableTransport(new NativeHttpTransport());
$provider = new UsersProvider(
    baseUrl: 'https://api.example.com',
    authorizationStrategy: new NullAuthorizationStrategy(),
    transport: $transport,
);

$response = $provider->getUser(123);
$lastRecord = $transport->getLastRecord();
```

Response DTO также может хранить локальные диагностические заметки:

```php
$response->addDiagnostic(['source' => 'fixture', 'case' => 'empty-list']);
$diagnostics = $response->getDiagnostics();
```

## Обработка ошибок

- Валидация prompt выбрасывает `InvalidArgumentException`, если обязательное поле отсутствует или пустое.
- Валидация фабрики запроса выбрасывает `ValidationException`, если endpoint содержит незаполненный path-плейсхолдер.
- Ошибки авторизации выбрасывают `AuthorizationException`.
- Ошибки транспорта выбрасывают `TransportException`.
- Успешные не-JSON ответы выбрасывают `ResponseDecodeException`.
- Ошибки создания Response DTO выбрасывают `ResponseHydrationException`.
- HTTP-ответы со статусом `400` и выше преобразуются в `ApiError` и доступны через `ResponseInterface::getError()`, включая не-JSON тела ошибок.
- Успешные ответы без обязательных полей выбрасывают `UnexpectedValueException`, если включён `strictValidation`.

## Лицензия

MIT.
