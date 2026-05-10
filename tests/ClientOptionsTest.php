<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests;

use and_y87\PhpClientSdk\Config\ClientOptions;
use and_y87\PhpClientSdk\Config\BaseUrl;
use and_y87\PhpClientSdk\Decoder\JsonResponseDecoder;
use and_y87\PhpClientSdk\Encoder\DefaultBodyEncoder;
use and_y87\PhpClientSdk\Encoder\DefaultQueryEncoder;
use and_y87\PhpClientSdk\Error\DefaultApiErrorFactory;
use and_y87\PhpClientSdk\Http\HeaderUtils;
use and_y87\PhpClientSdk\Request\DefaultRequestFinalizer;
use and_y87\PhpClientSdk\Request\DefaultRequestFactory;
use and_y87\PhpClientSdk\Retry\NoRetryPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет настройки клиента по умолчанию.
 */
class ClientOptionsTest extends TestCase
{
    /**
     * Проверяет, что ClientOptions создаёт безопасные default-компоненты.
     *
     * @return void
     */
    public function testDefaultOptionsUseStrictSafeComponents(): void
    {
        $options = new ClientOptions();

        self::assertSame(30, $options->timeout);
        self::assertTrue($options->strictValidation);
        self::assertInstanceOf(NoRetryPolicy::class, $options->retryPolicy);
        self::assertInstanceOf(DefaultQueryEncoder::class, $options->queryEncoder);
        self::assertInstanceOf(DefaultBodyEncoder::class, $options->bodyEncoder);
        self::assertInstanceOf(JsonResponseDecoder::class, $options->responseDecoder);
        self::assertInstanceOf(DefaultApiErrorFactory::class, $options->errorFactory);
        self::assertInstanceOf(DefaultRequestFactory::class, $options->requestFactory);
        self::assertInstanceOf(DefaultRequestFinalizer::class, $options->requestFinalizer);
        self::assertNull($options->authorizationResolver);
        self::assertSame([401], $options->refreshAuthorizationStatusCodes);
    }

    /**
     * Проверяет, что ClientOptions сразу отклоняет небезопасные заголовки.
     *
     * @return void
     */
    public function testOptionsRejectHeaderInjection(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ClientOptions(headers: ['X-Test' => "ok\r\nX-Injected: 1"]);
    }

    /**
     * Проверяет, что bool-значения заголовков сериализуются явно.
     *
     * @return void
     */
    public function testHeaderUtilsSerializesBooleanValuesExplicitly(): void
    {
        self::assertSame(
            [
                'X-Enabled' => 'true',
                'X-Disabled' => 'false',
            ],
            HeaderUtils::merge([], [
                'X-Enabled' => true,
                'X-Disabled' => false,
            ]),
        );
    }

    /**
     * Проверяет сборку базового URL из protocol, host, port и prefix.
     *
     * @return void
     */
    public function testBaseUrlBuildsStringFromParts(): void
    {
        $baseUrl = new BaseUrl(
            host: 'api.example.test',
            protocol: 'https',
            prefix: '/api/v1/',
            port: 8443,
        );

        self::assertSame('https://api.example.test:8443/api/v1', (string) $baseUrl);
    }
}
