<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Contracts;

use and_y87\PhpClientSdk\Http\HttpRequest;
use and_y87\PhpClientSdk\Http\HttpResponse;

/**
 * Описывает транспортный слой HTTP-запросов.
 */
interface HttpTransportInterface
{
    /**
     * Отправляет HTTP-запрос.
     *
     * @param HttpRequest $request Запрос.
     *
     * @return HttpResponse Ответ.
     *
     * @throws \RuntimeException Если транспорт не смог выполнить запрос.
     */
    public function send(HttpRequest $request): HttpResponse;
}
