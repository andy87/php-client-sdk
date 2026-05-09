<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Http;

/**
 * Хранит диагностическую запись одного HTTP-вызова.
 */
class HttpTraceRecord
{
    /**
     * Создаёт диагностическую запись HTTP-вызова.
     *
     * @param HttpRequest $request Отправленный HTTP-запрос.
     * @param HttpResponse|null $response Полученный HTTP-ответ или null при исключении.
     * @param \Throwable|null $exception Исключение транспорта или null при успешном ответе.
     * @param float $durationMs Длительность HTTP-вызова в миллисекундах.
     *
     * @return void
     */
    public function __construct(
        public readonly HttpRequest $request,
        public readonly ?HttpResponse $response,
        public readonly ?\Throwable $exception,
        public readonly float $durationMs,
    ) {
    }
}
