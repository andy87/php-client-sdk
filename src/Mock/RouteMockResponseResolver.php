<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Mock;

use and_y87\PhpClientSdk\Http\HeaderUtils;
use and_y87\PhpClientSdk\Http\HttpRequest;
use and_y87\PhpClientSdk\Http\HttpResponse;

/**
 * Resolver mock-ответов по HTTP-методу и URL, path или endpoint-шаблону.
 */
class RouteMockResponseResolver implements MockResponseResolverInterface
{
    /** @var list<array{method:string,pathOrUrl:string,response:HttpResponse}> Зарегистрированные mock-route. */
    private array $routes = [];

    /**
     * Добавляет готовый mock-ответ для route.
     *
     * @param string $method HTTP-метод.
     * @param string $pathOrUrl Абсолютный URL, path или endpoint-шаблон.
     * @param HttpResponse $response Mock-ответ.
     *
     * @return static Текущий resolver.
     *
     * @throws \InvalidArgumentException Если method или pathOrUrl пустые.
     */
    public function add(string $method, string $pathOrUrl, HttpResponse $response): static
    {
        $method = strtoupper(trim($method));
        $pathOrUrl = trim($pathOrUrl);

        if ($method === '') {
            throw new \InvalidArgumentException('Mock route method must be a non-empty string.');
        }

        if ($pathOrUrl === '') {
            throw new \InvalidArgumentException('Mock route path or URL must be a non-empty string.');
        }

        $this->routes[] = [
            'method' => $method,
            'pathOrUrl' => $pathOrUrl,
            'response' => $response,
        ];

        return $this;
    }

    /**
     * Добавляет JSON mock-ответ для route.
     *
     * @param string $method HTTP-метод.
     * @param string $pathOrUrl Абсолютный URL, path или endpoint-шаблон.
     * @param array<string, mixed>|list<mixed> $body Тело ответа.
     * @param int $statusCode HTTP-статус ответа.
     * @param array<string, string> $headers Дополнительные заголовки ответа.
     *
     * @return static Текущий resolver.
     *
     * @throws \JsonException Если тело ответа нельзя закодировать в JSON.
     * @throws \UnexpectedValueException Если JSON-кодирование вернуло некорректный тип.
     * @throws \InvalidArgumentException Если method, pathOrUrl или headers некорректны.
     */
    public function addJson(
        string $method,
        string $pathOrUrl,
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
            $method,
            $pathOrUrl,
            new HttpResponse($statusCode, $headers, $rawBody),
        );
    }

    /**
     * Возвращает mock-ответ по совпадению method и route.
     *
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return HttpResponse|null Mock-ответ или null.
     */
    public function resolve(HttpRequest $request): ?HttpResponse
    {
        $method = strtoupper(trim($request->method));

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if ($this->matches($route['pathOrUrl'], $request)) {
                return $route['response'];
            }
        }

        return null;
    }

    /**
     * Проверяет совпадение route с HTTP-запросом.
     *
     * @param string $pathOrUrl Абсолютный URL, path или endpoint-шаблон.
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return bool true, если route подходит запросу.
     */
    private function matches(string $pathOrUrl, HttpRequest $request): bool
    {
        $requestPath = parse_url($request->url, PHP_URL_PATH);
        $endpoint = $request->metadata['endpoint'] ?? null;

        $candidates = [$request->url];

        if (is_string($requestPath)) {
            $candidates[] = $requestPath;
        }

        if (is_string($endpoint)) {
            $candidates[] = $endpoint;
        }

        foreach ($candidates as $candidate) {
            if ($pathOrUrl === $candidate) {
                return true;
            }
        }

        return false;
    }
}
