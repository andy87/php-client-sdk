<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests;

use and_y87\PhpClientSdk\Http\HttpRequest;
use and_y87\PhpClientSdk\Http\HttpResponse;
use and_y87\PhpClientSdk\Mock\PromptClassMockResponseResolver;
use and_y87\PhpClientSdk\Tests\Support\CreateUserPrompt;
use and_y87\PhpClientSdk\Tests\Support\GetUserPrompt;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет resolver mock-ответов по классу Prompt DTO.
 */
class PromptClassMockResponseResolverTest extends TestCase
{
    /**
     * Проверяет, что mock-ответ находится по promptClass из metadata запроса.
     *
     * @return void
     */
    public function testResolveReturnsResponseByPromptClassMetadata(): void
    {
        $response = new HttpResponse(200, ['X-Test' => '1'], '{"id":1}');
        $resolver = (new PromptClassMockResponseResolver())->add(GetUserPrompt::class, $response);

        self::assertSame($response, $resolver->resolve($this->request(GetUserPrompt::class)));
    }

    /**
     * Проверяет, что неизвестный Prompt DTO не даёт fallback-ответ.
     *
     * @return void
     */
    public function testResolveReturnsNullForUnknownPromptClass(): void
    {
        $resolver = (new PromptClassMockResponseResolver())->add(
            GetUserPrompt::class,
            new HttpResponse(200, [], '{}'),
        );

        self::assertNull($resolver->resolve($this->request(CreateUserPrompt::class)));
    }

    /**
     * Проверяет, что addJson создаёт JSON fixture с mock-заголовками.
     *
     * @return void
     */
    public function testAddJsonCreatesJsonResponseWithMockHeaders(): void
    {
        $resolver = (new PromptClassMockResponseResolver())->addJson(
            GetUserPrompt::class,
            ['id' => 10, 'name' => 'Mock'],
            201,
            ['X-Custom' => 'ok'],
        );

        $response = $resolver->resolve($this->request(GetUserPrompt::class));

        self::assertNotNull($response);
        self::assertSame(201, $response->statusCode);
        self::assertSame('application/json', $response->headers['Content-Type']);
        self::assertSame('1', $response->headers['X-Mock-Response']);
        self::assertSame('ok', $response->headers['X-Custom']);
        self::assertSame('{"id":10,"name":"Mock"}', $response->body);
    }

    /**
     * Проверяет, что resolver отклоняет класс, который не является Prompt DTO.
     *
     * @return void
     */
    public function testAddRejectsNonPromptClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        (new PromptClassMockResponseResolver())->add(\stdClass::class, new HttpResponse(200, [], '{}'));
    }

    /**
     * Создаёт HTTP-запрос с указанным promptClass в metadata.
     *
     * @param string $promptClass Класс Prompt DTO.
     *
     * @return HttpRequest HTTP-запрос.
     */
    private function request(string $promptClass): HttpRequest
    {
        return new HttpRequest(
            method: 'GET',
            url: 'https://api.example.test/users/10',
            metadata: [
                'promptClass' => $promptClass,
            ],
        );
    }
}
