<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests;

use and_y87\PhpClientSdk\Auth\ApiKeyAuthorizationStrategy;
use and_y87\PhpClientSdk\Auth\ClientCredentialsAuthorizationStrategy;
use and_y87\PhpClientSdk\Auth\NullAuthorizationStrategy;
use and_y87\PhpClientSdk\Auth\PromptClassAuthorizationStrategyResolver;
use and_y87\PhpClientSdk\Cache\ArrayCache;
use and_y87\PhpClientSdk\Config\BaseUrl;
use and_y87\PhpClientSdk\Config\ClientOptions;
use and_y87\PhpClientSdk\Dto\ApiError;
use and_y87\PhpClientSdk\Exception\AuthorizationException;
use and_y87\PhpClientSdk\Exception\ResponseDecodeException;
use and_y87\PhpClientSdk\Exception\ResponseHydrationException;
use and_y87\PhpClientSdk\Exception\TransportException;
use and_y87\PhpClientSdk\Exception\ValidationException;
use and_y87\PhpClientSdk\Encoder\DefaultBodyEncoder;
use and_y87\PhpClientSdk\Encoder\MultipartBodyEncoder;
use and_y87\PhpClientSdk\Event\ClientEvents;
use and_y87\PhpClientSdk\Http\MultipartFile;
use and_y87\PhpClientSdk\Http\HttpResponse;
use and_y87\PhpClientSdk\Http\HttpRequest;
use and_y87\PhpClientSdk\Http\NativeHttpTransport;
use and_y87\PhpClientSdk\Mock\CallbackMockResponseResolver;
use and_y87\PhpClientSdk\Mock\CompositeMockResponseResolver;
use and_y87\PhpClientSdk\Mock\MockTransport;
use and_y87\PhpClientSdk\Mock\RouteMockResponseResolver;
use and_y87\PhpClientSdk\Prompt\AbstractPrompt;
use and_y87\PhpClientSdk\Request\DefaultRequestFactory;
use and_y87\PhpClientSdk\Response\AbstractResponse;
use and_y87\PhpClientSdk\Retry\DefaultRetryPolicy;
use and_y87\PhpClientSdk\Tests\Support\CreateUserPrompt;
use and_y87\PhpClientSdk\Tests\Support\FakeTransport;
use and_y87\PhpClientSdk\Tests\Support\GetUserPrompt;
use and_y87\PhpClientSdk\Tests\Support\TestProvider;
use and_y87\PhpClientSdk\Tests\Support\UserResponse;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет request pipeline базового provider-а.
 */
class ProviderPipelineTest extends TestCase
{
    /**
     * Проверяет успешный JSON-запрос на default-настройках.
     *
     * @return void
     */
    public function testDefaultRequestReturnsHydratedResponseWithMetadata(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, ['X-Request-Id' => 'abc'], '{"id":10,"name":"Ivan"}'),
        ]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 10, 'includePosts' => true]), UserResponse::class);

        self::assertSame(10, $response->id);
        self::assertSame('Ivan', $response->name);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['X-Request-Id' => 'abc'], $response->getHeaders());
        self::assertSame('{"id":10,"name":"Ivan"}', $response->getRawBody());
        self::assertSame(['id' => 10, 'name' => 'Ivan'], $response->getDecodedBody());
        self::assertNotNull($response->getRequest());
        self::assertSame('https://api.example.test/users/10', $transport->requests[0]->url);
        self::assertSame(['include_posts' => true], $transport->requests[0]->query);
        self::assertSame('include_posts=1', $transport->requests[0]->metadata['queryString']);
        self::assertSame(GetUserPrompt::class, $transport->requests[0]->metadata['promptClass']);
        self::assertSame('/users/{id}', $transport->requests[0]->metadata['endpoint']);
    }

    /**
     * Проверяет, что Prompt DTO валидируется по умолчанию.
     *
     * @return void
     */
    public function testPromptValidationIsEnabledByDefault(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(\InvalidArgumentException::class);

        $provider->call(new CreateUserPrompt(), UserResponse::class);
    }

    /**
     * Проверяет, что validatePrompt=false отключает вызов Prompt::validate().
     *
     * @return void
     */
    public function testPromptValidationCanBeDisabled(): void
    {
        $resolver = (new RouteMockResponseResolver())->addJson('POST', '/users', ['id' => 15, 'name' => 'Mock']);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            new MockTransport($resolver),
            options: new ClientOptions(validatePrompt: false),
        );

        /** @var UserResponse $response */
        $response = $provider->call(new CreateUserPrompt(), UserResponse::class);

        self::assertSame(15, $response->id);
        self::assertSame('Mock', $response->name);
        self::assertSame('1', $response->getHeaders()['X-Mock-Response']);
    }

    /**
     * Проверяет, что mock-route может совпадать с endpoint-шаблоном из metadata.
     *
     * @return void
     */
    public function testMockRouteCanMatchEndpointTemplate(): void
    {
        $resolver = (new RouteMockResponseResolver())->addJson('get', '/users/{id}', ['id' => 10, 'name' => 'Template']);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            new MockTransport($resolver),
        );

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 10]), UserResponse::class);

        self::assertSame(10, $response->id);
        self::assertSame('Template', $response->name);
    }

    /**
     * Проверяет, что mock-transport не делает fallback и падает при неизвестном route.
     *
     * @return void
     */
    public function testMockTransportThrowsWhenRouteIsMissing(): void
    {
        $transport = new MockTransport(new RouteMockResponseResolver());
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Mock response fixture was not found');

        $provider->call(new GetUserPrompt(['id' => 10]), UserResponse::class);
    }

    /**
     * Проверяет, что strictValidation=true валит неполный успешный mock-ответ.
     *
     * @return void
     */
    public function testStrictValidationStillRejectsIncompleteMockResponse(): void
    {
        $resolver = (new RouteMockResponseResolver())->addJson('GET', '/users/{id}', ['name' => 'No id']);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            new MockTransport($resolver),
        );

        $this->expectException(ResponseHydrationException::class);

        $provider->call(new GetUserPrompt(['id' => 10]), UserResponse::class);
    }

    /**
     * Проверяет callback и composite mock resolver-ы.
     *
     * @return void
     */
    public function testCompositeMockResolverUsesCallbackFallback(): void
    {
        $resolver = new CompositeMockResponseResolver([
            new RouteMockResponseResolver(),
            new CallbackMockResponseResolver(static function (HttpRequest $request): ?HttpResponse {
                if ($request->method !== 'GET') {
                    return null;
                }

                return new HttpResponse(200, ['Content-Type' => 'application/json'], '{"id":20,"name":"Callback"}');
            }),
        ]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            new MockTransport($resolver),
        );

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 20]), UserResponse::class);

        self::assertSame(20, $response->id);
        self::assertSame('Callback', $response->name);
    }

    /**
     * Проверяет кодирование JSON-тела запроса.
     *
     * @return void
     */
    public function testJsonBodyIsEncodedBeforeTransport(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $provider->call(new CreateUserPrompt(['name' => 'Ivan']), UserResponse::class);

        self::assertSame('{"name":"Ivan"}', $transport->requests[0]->rawBody);
        self::assertSame('application/json', $transport->requests[0]->headers['Content-Type']);
    }

    /**
     * Проверяет, что Prompt DTO может добавлять HTTP header-параметры в запрос.
     *
     * @return void
     */
    public function testPromptHeaderParametersAreMergedIntoRequestHeaders(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $provider->call(new CreateUserPrompt([
            'name' => 'Ivan',
            'requestSource' => 'crm',
        ]), UserResponse::class);

        self::assertSame('crm', $transport->requests[0]->headers['X-Request-Source']);
        self::assertSame(['name' => 'Ivan'], $transport->requests[0]->body);
    }

    /**
     * Проверяет, что HTTP-ошибка возвращается как Response с ApiError.
     *
     * @return void
     */
    public function testHttpErrorReturnsResponseWithApiError(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(400, ['X-Trace' => 't'], '{"error":{"code":123,"message":"Bad request","type":"validation"}}'),
        ]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $response = $provider->call(new GetUserPrompt(['id' => 5]), UserResponse::class);

        self::assertTrue($response->hasError());
        self::assertSame(400, $response->getStatusCode());
        $error = $response->getError();
        self::assertNotNull($error);
        self::assertSame(123, $error->code);
        self::assertSame('Bad request', $error->message);
        self::assertSame('{"error":{"code":123,"message":"Bad request","type":"validation"}}', $error->rawBody);
    }

    /**
     * Проверяет, что не-JSON тело HTTP-ошибки не ломает Response flow.
     *
     * @return void
     */
    public function testNonJsonHttpErrorStillReturnsResponse(): void
    {
        $transport = new FakeTransport([new HttpResponse(500, [], '<html>error</html>')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $response = $provider->call(new GetUserPrompt(['id' => 5]), UserResponse::class);

        self::assertTrue($response->hasError());
        self::assertSame(500, $response->getError()?->statusCode);
        self::assertSame('<html>error</html>', $response->getRawBody());
    }

    /**
     * Проверяет, что успешный не-JSON ответ считается ошибкой декодирования.
     *
     * @return void
     */
    public function testSuccessfulNonJsonResponseThrowsDecodeException(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '<html>ok</html>')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(ResponseDecodeException::class);

        $provider->call(new GetUserPrompt(['id' => 5]), UserResponse::class);
    }

    /**
     * Проверяет retry policy при включённой настройке.
     *
     * @return void
     */
    public function testRetryPolicyIsOptIn(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(503, [], '{"error":{"message":"busy"}}'),
            new HttpResponse(200, [], '{"id":7,"name":"Retry"}'),
        ]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(retryPolicy: new DefaultRetryPolicy(maxAttempts: 2, baseDelayMs: 0)),
        );

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 7]), UserResponse::class);

        self::assertSame('Retry', $response->name);
        self::assertCount(2, $transport->requests);
        self::assertSame(2, $transport->requests[1]->metadata['attempts']);
    }

    /**
     * Проверяет, что retry methods нормализуются независимо от регистра.
     *
     * @return void
     */
    public function testRetryPolicyNormalizesConfiguredMethods(): void
    {
        $policy = new DefaultRetryPolicy(maxAttempts: 2, methods: ['get']);

        self::assertTrue($policy->shouldRetry(
            1,
            new HttpRequest('GET', 'https://api.example.test'),
            new HttpResponse(503, [], '{}'),
        ));
    }

    /**
     * Проверяет query API key авторизацию.
     *
     * @return void
     */
    public function testApiKeyCanBeSentInQuery(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new ApiKeyAuthorizationStrategy('api_key', 'secret', ApiKeyAuthorizationStrategy::LOCATION_QUERY),
            $transport,
        );

        $provider->call(new GetUserPrompt(['id' => 1]), UserResponse::class);

        self::assertSame('https://api.example.test/users/1', $transport->requests[0]->url);
        self::assertSame(['api_key' => 'secret'], $transport->requests[0]->query);
        self::assertSame('api_key=secret', $transport->requests[0]->metadata['queryString']);
    }

    /**
     * Проверяет выбор стратегии авторизации по классу Prompt DTO.
     *
     * @return void
     */
    public function testAuthorizationResolverCanOverrideStrategyByPromptClass(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(authorizationResolver: new PromptClassAuthorizationStrategyResolver([
                GetUserPrompt::class => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
            ])),
        );

        $provider->call(new GetUserPrompt(['id' => 1]), UserResponse::class);

        self::assertSame('secret', $transport->requests[0]->headers['X-Api-Key']);
    }

    /**
     * Проверяет, что provider принимает составной BaseUrl без изменения Prompt DTO.
     *
     * @return void
     */
    public function testProviderAcceptsBaseUrlValueObject(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            new BaseUrl(host: 'api.example.test', prefix: 'v1'),
            new NullAuthorizationStrategy(),
            $transport,
        );

        $provider->call(new GetUserPrompt(['id' => 1]), UserResponse::class);

        self::assertSame('https://api.example.test/v1/users/1', $transport->requests[0]->url);
    }

    /**
     * Проверяет автоматическое обновление OAuth-токена и один повтор после 401.
     *
     * @return void
     */
    public function testRefreshableAuthorizationRetriesOnceAfterUnauthorizedResponse(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"access_token":"first-token","expires_in":3600}'),
            new HttpResponse(401, [], '{"error":{"message":"expired"}}'),
            new HttpResponse(200, [], '{"access_token":"second-token","expires_in":3600}'),
            new HttpResponse(200, [], '{"id":1,"name":"Refreshed"}'),
        ]);
        $provider = new TestProvider(
            'https://api.example.test',
            new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret'),
            $transport,
        );

        /** @var UserResponse $response */
        $response = $provider->call(new GetUserPrompt(['id' => 1]), UserResponse::class);

        self::assertSame('Refreshed', $response->name);
        self::assertCount(4, $transport->requests);
        self::assertSame('https://auth.example.test/token', $transport->requests[0]->url);
        self::assertSame('Bearer first-token', $transport->requests[1]->headers['Authorization']);
        self::assertSame('https://auth.example.test/token', $transport->requests[2]->url);
        self::assertSame('Bearer second-token', $transport->requests[3]->headers['Authorization']);
        self::assertTrue($response->getRequest()?->metadata['authorizationRefreshed'] ?? false);
    }

    /**
     * Проверяет, что список статусов refresh-retry можно отключить.
     *
     * @return void
     */
    public function testRefreshAuthorizationStatusCodesCanDisableUnauthorizedRetry(): void
    {
        $transport = new FakeTransport([
            new HttpResponse(200, [], '{"access_token":"first-token","expires_in":3600}'),
            new HttpResponse(401, [], '{"error":{"message":"expired"}}'),
        ]);
        $provider = new TestProvider(
            'https://api.example.test',
            new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret'),
            $transport,
            options: new ClientOptions(refreshAuthorizationStatusCodes: []),
        );

        $response = $provider->call(new GetUserPrompt(['id' => 1]), UserResponse::class);

        self::assertTrue($response->hasError());
        self::assertSame(401, $response->getStatusCode());
        self::assertCount(2, $transport->requests);
    }

    /**
     * Проверяет, что изменения query в BEFORE_REQUEST попадают в финальную query-string metadata.
     *
     * @return void
     */
    public function testBeforeRequestQueryMutationIsFinalized(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(events: [
                ClientEvents::BEFORE_REQUEST => static function (object $event): void {
                    $event->request->query['debug'] = '1';
                },
            ]),
        );

        $provider->call(new GetUserPrompt(['id' => 1, 'includePosts' => true]), UserResponse::class);

        self::assertSame(['include_posts' => true, 'debug' => '1'], $transport->requests[0]->query);
        self::assertSame('include_posts=1&debug=1', $transport->requests[0]->metadata['queryString']);
    }

    /**
     * Проверяет, что изменения тела в BEFORE_REQUEST попадают в rawBody перед отправкой.
     *
     * @return void
     */
    public function testBeforeRequestBodyMutationIsFinalized(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(events: [
                ClientEvents::BEFORE_REQUEST => static function (object $event): void {
                    $event->request->body = ['name' => 'Petr'];
                },
            ]),
        );

        $provider->call(new CreateUserPrompt(['name' => 'Ivan']), UserResponse::class);

        self::assertSame(['name' => 'Petr'], $transport->requests[0]->body);
        self::assertSame('{"name":"Petr"}', $transport->requests[0]->rawBody);
        self::assertSame('application/json', $transport->requests[0]->headers['Content-Type']);
    }

    /**
     * Проверяет, что удаление тела в BEFORE_REQUEST очищает ранее закодированное rawBody.
     *
     * @return void
     */
    public function testBeforeRequestBodyRemovalClearsRawBody(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(events: [
                ClientEvents::BEFORE_REQUEST => static function (object $event): void {
                    $event->request->body = null;
                },
            ]),
        );

        $provider->call(new CreateUserPrompt(['name' => 'Ivan']), UserResponse::class);

        self::assertNull($transport->requests[0]->body);
        self::assertNull($transport->requests[0]->rawBody);
    }

    /**
     * Проверяет, что фабрика запроса получает тело Prompt DTO только один раз.
     *
     * @return void
     */
    public function testRequestFactoryReadsPromptBodyOnce(): void
    {
        $prompt = new class extends AbstractPrompt {
            protected const METHOD = 'POST';
            protected const ENDPOINT = '/users';
            protected const CONTENT_TYPE = 'application/json';

            public int $bodyCalls = 0;

            /**
             * Возвращает тело запроса и считает количество вызовов.
             *
             * @return array<string, string> Тело запроса.
             */
            public function getBody(): array
            {
                ++$this->bodyCalls;

                return $this->bodyCalls === 1 ? ['name' => 'First'] : ['name' => 'Second'];
            }
        };

        $request = (new DefaultRequestFactory())->create($prompt, 'https://api.example.test', [], 30);

        self::assertSame(1, $prompt->bodyCalls);
        self::assertSame(['name' => 'First'], $request->body);
        self::assertSame('{"name":"First"}', $request->rawBody);
    }

    /**
     * Проверяет, что OAuth token request содержит закодированное form-urlencoded тело.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationUsesRawFormBody(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"access_token":"token","expires_in":3600}')]);
        $authorization = new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret');

        $headers = $authorization->getAuthorizationHeaders($transport);

        self::assertSame(['Authorization' => 'Bearer token'], $headers);
        self::assertSame('grant_type=client_credentials&client_id=client&client_secret=secret', $transport->requests[0]->rawBody);
        self::assertSame('application/x-www-form-urlencoded', $transport->requests[0]->headers['Content-Type']);
    }

    /**
     * Проверяет, что OAuth client_credentials может получить токен через mock-route.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationCanUseMockTransport(): void
    {
        $resolver = (new RouteMockResponseResolver())->addJson(
            'POST',
            'https://auth.example.test/token',
            ['access_token' => 'mock-token', 'expires_in' => 3600],
        );
        $authorization = new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'bad', 'bad');

        $headers = $authorization->getAuthorizationHeaders(new MockTransport($resolver));

        self::assertSame(['Authorization' => 'Bearer mock-token'], $headers);
    }

    /**
     * Проверяет, что OAuth client_credentials переиспользует access token из внешнего кеша.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationReusesExternalTokenCache(): void
    {
        $cache = new ArrayCache();
        $firstTransport = new FakeTransport([new HttpResponse(200, [], '{"access_token":"cached-token","expires_in":3600}')]);
        $firstAuthorization = new ClientCredentialsAuthorizationStrategy(
            'https://auth.example.test/token',
            'client',
            'secret',
            tokenCache: $cache,
            tokenCacheKey: 'oauth:test',
        );

        self::assertSame(['Authorization' => 'Bearer cached-token'], $firstAuthorization->getAuthorizationHeaders($firstTransport));
        self::assertCount(1, $firstTransport->requests);

        $secondTransport = new FakeTransport([]);
        $secondAuthorization = new ClientCredentialsAuthorizationStrategy(
            'https://auth.example.test/token',
            'client',
            'secret',
            tokenCache: $cache,
            tokenCacheKey: 'oauth:test',
        );

        self::assertSame(['Authorization' => 'Bearer cached-token'], $secondAuthorization->getAuthorizationHeaders($secondTransport));
        self::assertCount(0, $secondTransport->requests);
    }

    /**
     * Проверяет, что OAuth client_credentials обновляет внешний токен заранее по clock skew.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationRefreshesExternalTokenBeforeExpiration(): void
    {
        $cache = new ArrayCache();
        $cache->set('oauth:test', [
            'access_token' => 'almost-expired-token',
            'expires_at' => time() + 30,
        ], 30);
        $transport = new FakeTransport([new HttpResponse(200, [], '{"access_token":"fresh-token","expires_in":3600}')]);
        $authorization = new ClientCredentialsAuthorizationStrategy(
            'https://auth.example.test/token',
            'client',
            'secret',
            tokenCache: $cache,
            tokenCacheKey: 'oauth:test',
            clockSkew: 60,
        );

        self::assertSame(['Authorization' => 'Bearer fresh-token'], $authorization->getAuthorizationHeaders($transport));
        self::assertCount(1, $transport->requests);

        $cachedToken = $cache->get('oauth:test');
        self::assertIsArray($cachedToken);
        self::assertSame('fresh-token', $cachedToken['access_token'] ?? null);
        self::assertGreaterThan(time() + 3500, $cachedToken['expires_at'] ?? 0);
    }

    /**
     * Проверяет, что свежезапрошенный короткоживущий токен возвращается даже при большом clock skew.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationReturnsFreshShortLivedToken(): void
    {
        $cache = new ArrayCache();
        $transport = new FakeTransport([new HttpResponse(200, [], '{"access_token":"short-token","expires_in":30}')]);
        $authorization = new ClientCredentialsAuthorizationStrategy(
            'https://auth.example.test/token',
            'client',
            'secret',
            tokenCache: $cache,
            tokenCacheKey: 'oauth:test',
            clockSkew: 60,
        );

        self::assertSame(['Authorization' => 'Bearer short-token'], $authorization->getAuthorizationHeaders($transport));
        self::assertCount(1, $transport->requests);
    }

    /**
     * Проверяет, что refreshAuthorization сбрасывает внешний токен и сохраняет новый.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationRefreshAuthorizationReplacesExternalToken(): void
    {
        $cache = new ArrayCache();
        $cache->set('oauth:test', [
            'access_token' => 'old-token',
            'expires_at' => time() + 3600,
        ], 3600);
        $transport = new FakeTransport([new HttpResponse(200, [], '{"access_token":"new-token","expires_in":3600}')]);
        $authorization = new ClientCredentialsAuthorizationStrategy(
            'https://auth.example.test/token',
            'client',
            'secret',
            tokenCache: $cache,
            tokenCacheKey: 'oauth:test',
        );

        $authorization->refreshAuthorization($transport);

        $cachedToken = $cache->get('oauth:test');
        self::assertCount(1, $transport->requests);
        self::assertIsArray($cachedToken);
        self::assertSame('new-token', $cachedToken['access_token'] ?? null);
    }

    /**
     * Проверяет, что ошибка декодирования OAuth-ответа оборачивается в AuthorizationException.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationWrapsDecodeFailure(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], 'not-json')]);
        $authorization = new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret');

        try {
            $authorization->getAuthorizationHeaders($transport);
            self::fail('AuthorizationException was not thrown.');
        } catch (AuthorizationException $exception) {
            self::assertInstanceOf(ResponseDecodeException::class, $exception->getPrevious());
        }
    }

    /**
     * Проверяет, что OAuth access_token должен быть непустой строкой.
     *
     * @return void
     */
    public function testClientCredentialsAuthorizationRejectsInvalidTokenType(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"access_token":[],"expires_in":3600}')]);
        $authorization = new ClientCredentialsAuthorizationStrategy('https://auth.example.test/token', 'client', 'secret');

        $this->expectException(AuthorizationException::class);

        $authorization->getAuthorizationHeaders($transport);
    }

    /**
     * Проверяет, что response class должен реализовывать ResponseInterface.
     *
     * @return void
     */
    public function testProviderRejectsInvalidResponseClass(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(\InvalidArgumentException::class);

        $provider->callAnyResponseClass(new GetUserPrompt(['id' => 1]), \stdClass::class);
    }

    /**
     * Проверяет, что Prompt без обязательных constants падает понятной ошибкой.
     *
     * @return void
     */
    public function testPromptWithoutMethodConstantThrowsLogicException(): void
    {
        $prompt = new class extends AbstractPrompt {
        };

        $this->expectException(\LogicException::class);

        $prompt->getMethod();
    }

    /**
     * Проверяет ошибку незаполненного path-параметра.
     *
     * @return void
     */
    public function testMissingPathParameterThrowsValidationException(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, [], '{}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);

        $this->expectException(ValidationException::class);

        $provider->call(new class(['id' => 1]) extends GetUserPrompt {
            protected const PATH_FIELDS = [];
        }, UserResponse::class);
    }

    /**
     * Проверяет, что multipart Content-Type содержит фактический boundary.
     *
     * @return void
     */
    public function testMultipartBodyAddsBoundaryToContentType(): void
    {
        $body = (new MultipartBodyEncoder())->encode(['file' => 'abc'], 'multipart/form-data');

        self::assertStringContainsString('boundary=', $body->contentType ?? '');
        self::assertStringContainsString('--' . substr((string) $body->contentType, strpos((string) $body->contentType, 'boundary=') + 9), $body->content ?? '');
    }

    /**
     * Проверяет, что multipart encoder использует переданный boundary.
     *
     * @return void
     */
    public function testMultipartBodyUsesProvidedBoundary(): void
    {
        $body = (new MultipartBodyEncoder())->encode(['file' => 'abc'], 'multipart/form-data; boundary=test-boundary');

        self::assertSame('multipart/form-data; boundary=test-boundary', $body->contentType);
        self::assertStringContainsString('--test-boundary', $body->content ?? '');
    }

    /**
     * Проверяет, что multipart field name не может внедрить дополнительный header.
     *
     * @return void
     */
    public function testMultipartBodyRejectsHeaderInjectionInFieldName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new MultipartBodyEncoder())->encode(["file\r\nX-Injected: 1" => 'abc'], 'multipart/form-data');
    }

    /**
     * Проверяет, что multipart filename не может внедрить дополнительный header.
     *
     * @return void
     */
    public function testMultipartBodyRejectsHeaderInjectionInFilename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'php-client-sdk');
        self::assertIsString($path);
        file_put_contents($path, 'abc');

        try {
            $this->expectException(\InvalidArgumentException::class);

            (new MultipartBodyEncoder())->encode([
                'file' => new MultipartFile($path, "evil\r\nX-Injected: 1.txt"),
            ], 'multipart/form-data');
        } finally {
            unlink($path);
        }
    }

    /**
     * Проверяет, что multipart boundary не принимает символы внедрения заголовков.
     *
     * @return void
     */
    public function testMultipartBodyRejectsInvalidBoundary(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new MultipartBodyEncoder())->encode(['file' => 'abc'], "multipart/form-data; boundary=bad\r\nX-Injected: 1");
    }

    /**
     * Проверяет регистронезависимый выбор encoder-а по Content-Type.
     *
     * @return void
     */
    public function testBodyEncoderMatchesContentTypeCaseInsensitively(): void
    {
        $body = (new DefaultBodyEncoder())->encode(['a' => 1], 'Application/X-WWW-FORM-URLENCODED');

        self::assertSame('a=1', $body->content);
        self::assertSame('Application/X-WWW-FORM-URLENCODED', $body->contentType);
    }

    /**
     * Проверяет, что Native transport fallback нормализует Content-Type без учёта регистра.
     *
     * @return void
     */
    public function testNativeTransportNormalizesFallbackContentType(): void
    {
        $transport = new NativeHttpTransport();
        $mediaType = new \ReflectionMethod($transport, 'mediaType');
        $mediaType->setAccessible(true);

        self::assertSame(
            'application/x-www-form-urlencoded',
            $mediaType->invoke($transport, 'Application/X-WWW-FORM-URLENCODED; charset=utf-8'),
        );
    }

    /**
     * Проверяет, что Native transport fallback использует единый query encoder.
     *
     * @return void
     */
    public function testNativeTransportFallbackQueryEncodingUsesRfc3986(): void
    {
        $transport = new NativeHttpTransport();
        $buildUrl = new \ReflectionMethod($transport, 'buildUrl');
        $buildUrl->setAccessible(true);

        self::assertSame(
            'https://api.example.test/search?q=a%20b',
            $buildUrl->invoke($transport, 'https://api.example.test/search', ['q' => 'a b']),
        );
    }

    /**
     * Проверяет, что root body Prompt отправляет значение тела целиком, а не оборачивает его в поле.
     *
     * @return void
     *
     * @throws \RuntimeException Если request pipeline завершился ошибкой.
     */
    public function testRootBodyPromptSendsBodyValueDirectly(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, ['Content-Type' => 'application/json'], '{}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);
        $responseClass = get_class(new class extends AbstractResponse {
        });

        $provider->call(new class(['body' => ['ids' => [1, 2]]]) extends AbstractPrompt {
            protected const METHOD = 'POST';
            protected const ENDPOINT = '/bulk';
            protected const CONTENT_TYPE = 'application/json';
            protected const FIELD_MAP = ['body' => 'body'];
            protected const REQUIRED_FIELDS = ['body'];
            protected const BODY_ROOT_FIELD = 'body';

            /** @var array<string, mixed> Тело запроса целиком. */
            public array $body;
        }, $responseClass);

        self::assertSame(['ids' => [1, 2]], $transport->requests[0]->body);
        self::assertSame('{"ids":[1,2]}', $transport->requests[0]->rawBody);
    }

    /**
     * Проверяет OpenAPI form/explode кодирование массивов query-параметров.
     *
     * @return void
     *
     * @throws \RuntimeException Если request pipeline завершился ошибкой.
     */
    public function testQueryParameterStylesEncodeExplodedArrays(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, ['Content-Type' => 'application/json'], '{}')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);
        $responseClass = get_class(new class extends AbstractResponse {
        });

        $provider->call(new class(['ids' => [10, 20]]) extends AbstractPrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/items';
            protected const FIELD_MAP = ['ids' => 'ids'];
            protected const QUERY_FIELDS = ['ids'];
            protected const QUERY_PARAMETER_STYLES = ['ids' => ['style' => 'form', 'explode' => true]];

            /** @var array<int, int> Идентификаторы сущностей. */
            public array $ids;
        }, $responseClass);

        self::assertSame('ids=10&ids=20', $transport->requests[0]->metadata['queryString']);
    }

    /**
     * Проверяет raw body для application/octet-stream.
     *
     * @return void
     */
    public function testBodyEncoderSupportsOctetStreamRawBody(): void
    {
        $body = (new DefaultBodyEncoder())->encode('raw-bytes', 'application/octet-stream');

        self::assertSame('raw-bytes', $body->content);
        self::assertSame('application/octet-stream', $body->contentType);
    }

    /**
     * Проверяет, что успешный non-JSON ответ сохраняется как rawBody без ошибки декодирования.
     *
     * @return void
     *
     * @throws \RuntimeException Если request pipeline завершился ошибкой.
     */
    public function testSuccessfulNonJsonResponseKeepsRawBody(): void
    {
        $transport = new FakeTransport([new HttpResponse(200, ['Content-Type' => 'application/pdf'], '%PDF-1.4')]);
        $provider = new TestProvider('https://api.example.test', new NullAuthorizationStrategy(), $transport);
        $responseClass = get_class(new class extends AbstractResponse {
        });

        $response = $provider->call(new class extends AbstractPrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/document.pdf';
        }, $responseClass);

        self::assertSame('%PDF-1.4', $response->getRawBody());
        self::assertSame([], $response->getDecodedBody());
    }

    /**
     * Проверяет, что строковый machine-readable code ошибки API сохраняется без приведения к int.
     *
     * @return void
     */
    public function testApiErrorPreservesStringCode(): void
    {
        $error = new ApiError(['error' => ['code' => 'invalid_request', 'message' => 'Bad request']], 400);

        self::assertSame('invalid_request', $error->code);
        self::assertSame(400, $error->statusCode);
    }

    /**
     * Проверяет, что некорректный MODEL response DTO не игнорируется молча.
     *
     * @return void
     */
    public function testResponseRejectsMissingModelClass(): void
    {
        $this->expectException(\LogicException::class);

        new class(['id' => 1]) extends AbstractResponse {
            /** @phpstan-ignore-next-line Intentionally invalid class-string for runtime validation. */
            protected const MODEL = 'and_y87\\PhpClientSdk\\Tests\\MissingResponseModel';
        };
    }

    /**
     * Проверяет diagnostic log у response DTO.
     *
     * @return void
     */
    public function testResponseDiagnosticsCanBeCollected(): void
    {
        $response = new UserResponse(['id' => 1, 'name' => 'Ivan']);

        $response
            ->addDiagnostic('raw payload kept for investigation')
            ->addDiagnostic(['field' => 'name', 'status' => 'checked']);

        self::assertSame([
            'raw payload kept for investigation',
            ['field' => 'name', 'status' => 'checked'],
        ], $response->getDiagnostics());
    }
}
