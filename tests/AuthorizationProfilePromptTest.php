<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests;

use and_y87\PhpClientSdk\Security\Authorization\Strategy\ApiKeyAuthorizationStrategy;
use and_y87\PhpClientSdk\Security\Authorization\Resolver\AuthorizationProfileStrategyResolver;
use and_y87\PhpClientSdk\Security\Authorization\Strategy\BearerTokenAuthorizationStrategy;
use and_y87\PhpClientSdk\Security\Authorization\Strategy\NullAuthorizationStrategy;
use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;
use and_y87\PhpClientSdk\Request\Prompt\AbstractPrompt;
use and_y87\PhpClientSdk\Request\Prompt\PrivatePrompt;
use and_y87\PhpClientSdk\Request\Prompt\PublicPrompt;
use and_y87\PhpClientSdk\Tests\Support\FakeTransport;
use and_y87\PhpClientSdk\Tests\Support\TestProvider;
use and_y87\PhpClientSdk\Tests\Support\UserResponse;
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
