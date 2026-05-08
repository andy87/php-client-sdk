<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests;

use Andy87\ClientsBase\Auth\ApiKeyAuthorizationStrategy;
use Andy87\ClientsBase\Auth\AuthorizationProfileStrategyResolver;
use Andy87\ClientsBase\Auth\BearerTokenAuthorizationStrategy;
use Andy87\ClientsBase\Auth\NullAuthorizationStrategy;
use Andy87\ClientsBase\Config\ClientOptions;
use Andy87\ClientsBase\Http\HttpResponse;
use Andy87\ClientsBase\Prompt\AbstractPrompt;
use Andy87\ClientsBase\Prompt\PrivatePrompt;
use Andy87\ClientsBase\Prompt\PublicPrompt;
use Andy87\ClientsBase\Tests\Support\FakeTransport;
use Andy87\ClientsBase\Tests\Support\TestProvider;
use Andy87\ClientsBase\Tests\Support\UserResponse;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет PublicPrompt, PrivatePrompt и resolver стратегий по профилю авторизации.
 */
class AuthorizationProfilePromptTest extends TestCase
{
    /**
     * Проверяет, что PublicPrompt отключает авторизацию.
     *
     * @return void
     */
    public function testPublicPromptDoesNotRequireAuthorization(): void
    {
        $prompt = new class extends PublicPrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/public';
        };

        self::assertFalse($prompt->requiresAuthorization());
    }

    /**
     * Проверяет дефолтный профиль PrivatePrompt.
     *
     * @return void
     */
    public function testPrivatePromptRequiresAuthorizationAndUsesDefaultProfile(): void
    {
        $prompt = new class extends PrivatePrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/private';
        };

        self::assertTrue($prompt->requiresAuthorization());
        self::assertSame('default', $prompt->getAuthorizationProfile());
    }

    /**
     * Проверяет переопределение профиля авторизации в наследнике PrivatePrompt.
     *
     * @return void
     */
    public function testPrivatePromptCanOverrideAuthorizationProfile(): void
    {
        $prompt = new class extends PrivatePrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/private';
            protected const AUTHORIZATION_PROFILE = 'api-key';
        };

        self::assertSame('api-key', $prompt->getAuthorizationProfile());
    }

    /**
     * Проверяет выбор стратегии авторизации по профилю Prompt DTO.
     *
     * @return void
     */
    public function testAuthorizationProfileResolverSelectsStrategyByProfile(): void
    {
        $prompt = new class extends PrivatePrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/private';
            protected const AUTHORIZATION_PROFILE = 'api-key';
        };
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new NullAuthorizationStrategy(),
            $transport,
            options: new ClientOptions(authorizationResolver: new AuthorizationProfileStrategyResolver([
                'api-key' => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
            ])),
        );

        $provider->call($prompt, UserResponse::class);

        self::assertSame('secret', $transport->requests[0]->headers['X-Api-Key']);
    }

    /**
     * Проверяет fallback к default strategy при неизвестном профиле.
     *
     * @return void
     */
    public function testUnknownProfileFallsBackToDefaultStrategy(): void
    {
        $prompt = new class extends PrivatePrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/private';
            protected const AUTHORIZATION_PROFILE = 'missing-profile';
        };
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new BearerTokenAuthorizationStrategy('default-token'),
            $transport,
            options: new ClientOptions(authorizationResolver: new AuthorizationProfileStrategyResolver([
                'api-key' => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
            ])),
        );

        $provider->call($prompt, UserResponse::class);

        self::assertSame('Bearer default-token', $transport->requests[0]->headers['Authorization']);
    }

    /**
     * Проверяет, что обычный AbstractPrompt продолжает работать без auth profile.
     *
     * @return void
     */
    public function testPlainAbstractPromptFallsBackToDefaultStrategy(): void
    {
        $prompt = new class extends AbstractPrompt {
            protected const METHOD = 'GET';
            protected const ENDPOINT = '/plain';
        };
        $transport = new FakeTransport([new HttpResponse(200, [], '{"id":1}')]);
        $provider = new TestProvider(
            'https://api.example.test',
            new BearerTokenAuthorizationStrategy('default-token'),
            $transport,
            options: new ClientOptions(authorizationResolver: new AuthorizationProfileStrategyResolver([
                'api-key' => new ApiKeyAuthorizationStrategy('X-Api-Key', 'secret'),
            ])),
        );

        $provider->call($prompt, UserResponse::class);

        self::assertSame('Bearer default-token', $transport->requests[0]->headers['Authorization']);
    }
}
