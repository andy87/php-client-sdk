<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Mock;

use Andy87\PhpClientSdk\Contracts\HttpTransportInterface;
use Andy87\PhpClientSdk\Exception\TransportException;
use Andy87\PhpClientSdk\Http\HttpRequest;
use Andy87\PhpClientSdk\Http\HttpResponse;

/**
 * HTTP-транспорт для mock-режима, возвращающий заранее подготовленные ответы без сетевых запросов.
 */
class MockTransport implements HttpTransportInterface
{
    /**
     * Создаёт mock-транспорт.
     *
     * @param MockResponseResolverInterface $resolver Resolver mock-ответов.
     *
     * @return void
     */
    public function __construct(
        private MockResponseResolverInterface $resolver,
    ) {
    }

    /**
     * Возвращает mock-ответ для HTTP-запроса.
     *
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return HttpResponse Mock-ответ.
     *
     * @throws TransportException Если fixture для запроса не найдена.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        $response = $this->resolver->resolve($request);

        if ($response !== null) {
            return $response;
        }

        throw new TransportException(sprintf(
            'Mock response fixture was not found for "%s %s".',
            strtoupper($request->method),
            $request->url,
        ));
    }
}
