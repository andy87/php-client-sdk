<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Mock;

use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Resolver mock-ответов, делегирующий выбор ответа пользовательскому callback.
 */
class CallbackMockResponseResolver implements MockResponseResolverInterface
{
    /** @var \Closure */
    private \Closure $callback;

    /**
     * Создаёт callback resolver.
     *
     * @param callable $callback Callback вида fn(HttpRequest $request): ?HttpResponse.
     *
     * @return void
     */
    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    /**
     * Возвращает mock-ответ из callback.
     *
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return HttpResponse|null Mock-ответ или null.
     *
     * @throws \UnexpectedValueException Если callback вернул значение некорректного типа.
     */
    public function resolve(HttpRequest $request): ?HttpResponse
    {
        $response = ($this->callback)($request);

        if ($response === null || $response instanceof HttpResponse) {
            return $response;
        }

        throw new \UnexpectedValueException('Mock response callback must return HttpResponse or null.');
    }
}
