<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Mock;

use Andy87\PhpClientSdk\Contracts\PromptInterface;
use Andy87\PhpClientSdk\Http\HeaderUtils;
use Andy87\PhpClientSdk\Http\HttpRequest;
use Andy87\PhpClientSdk\Http\HttpResponse;

/**
 * Resolver mock-ответов по классу Prompt DTO из metadata HTTP-запроса.
 */
class PromptClassMockResponseResolver implements MockResponseResolverInterface
{
    /** @var array<class-string<PromptInterface>, HttpResponse> Mock-ответы по классу Prompt DTO. */
    private array $responses = [];

    /**
     * Добавляет готовый mock-ответ для класса Prompt DTO.
     *
     * @param string $promptClass Класс Prompt DTO.
     * @param HttpResponse $response Mock-ответ.
     *
     * @return static Текущий resolver.
     *
     * @throws \InvalidArgumentException Если класс Prompt DTO не существует или не реализует PromptInterface.
     */
    public function add(string $promptClass, HttpResponse $response): static
    {
        $this->assertPromptClass($promptClass);
        $this->responses[$promptClass] = $response;

        return $this;
    }

    /**
     * Добавляет JSON mock-ответ для класса Prompt DTO.
     *
     * @param string $promptClass Класс Prompt DTO.
     * @param array<string, mixed>|list<mixed> $body Тело ответа.
     * @param int $statusCode HTTP-статус ответа.
     * @param array<string, string> $headers Дополнительные заголовки ответа.
     *
     * @return static Текущий resolver.
     *
     * @throws \JsonException Если тело ответа нельзя закодировать в JSON.
     * @throws \UnexpectedValueException Если JSON-кодирование вернуло некорректный тип.
     * @throws \InvalidArgumentException Если класс Prompt DTO или заголовки некорректны.
     */
    public function addJson(
        string $promptClass,
        array $body,
        int $statusCode = 200,
        array $headers = [],
    ): static {
        $headers = HeaderUtils::merge([
            'Content-Type' => 'application/json',
            'X-Mock-Response' => '1',
        ], $headers);
        $rawBody = json_encode($body, JSON_THROW_ON_ERROR);

        if (!is_string($rawBody)) {
            throw new \UnexpectedValueException('Mock JSON response body must be encoded to string.');
        }

        return $this->add(
            $promptClass,
            new HttpResponse($statusCode, $headers, $rawBody),
        );
    }

    /**
     * Возвращает mock-ответ по классу Prompt DTO из metadata запроса.
     *
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return HttpResponse|null Mock-ответ или null.
     */
    public function resolve(HttpRequest $request): ?HttpResponse
    {
        $promptClass = $request->metadata['promptClass'] ?? null;

        if (!is_string($promptClass)) {
            return null;
        }

        return $this->responses[$promptClass] ?? null;
    }

    /**
     * Проверяет, что переданный класс является Prompt DTO.
     *
     * @param string $promptClass Класс Prompt DTO.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если класс Prompt DTO не существует или не реализует PromptInterface.
     */
    private function assertPromptClass(string $promptClass): void
    {
        if (!class_exists($promptClass)) {
            throw new \InvalidArgumentException(sprintf('Prompt class "%s" must exist.', $promptClass));
        }

        if (!is_a($promptClass, PromptInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Prompt class "%s" must implement %s.',
                $promptClass,
                PromptInterface::class,
            ));
        }
    }
}
