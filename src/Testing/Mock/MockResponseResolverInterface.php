<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Testing\Mock;

use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

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
