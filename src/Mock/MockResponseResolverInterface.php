<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Mock;

use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;

/**
 * Описывает resolver mock-ответов по данным HTTP-запроса.
 */
interface MockResponseResolverInterface
{
    /**
     * Возвращает mock-ответ для запроса или null, если resolver не нашёл подходящую fixture.
     *
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return HttpResponse|null Mock-ответ или null.
     */
    public function resolve(HttpRequest $request): ?HttpResponse;
}
