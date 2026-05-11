<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Transport\Trace;

use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

/**
 * Диагностический transport-wrapper, записывающий HTTP-запросы, ответы, исключения и длительность.
 */
class TraceableTransport implements HttpTransportInterface
{
    /** @var list<HttpTraceRecord> Диагностические записи HTTP-вызовов. */
    private array $records = [];

    /**
     * Создаёт transport-wrapper вокруг реального транспорта.
     *
     * @param HttpTransportInterface $transport Внутренний HTTP-транспорт.
     *
     * @return void
     */
    public function __construct(
        private HttpTransportInterface $transport,
    ) {}

    /**
     * Отправляет HTTP-запрос через внутренний транспорт и записывает trace.
     *
     * @param HttpRequest $request Запрос.
     *
     * @return HttpResponse Ответ внутреннего транспорта.
     *
     * @throws \Throwable Если внутренний транспорт выбросил исключение.
     */
    public function send(HttpRequest $request): HttpResponse
    {
        $startedAt = hrtime(true);

        try {
            $response = $this->transport->send($request);
        } catch (\Throwable $exception) {
            $this->records[] = new HttpTraceRecord(
                request: $request,
                response: null,
                exception: $exception,
                durationMs: $this->durationMs($startedAt),
            );

            throw $exception;
        }

        $this->records[] = new HttpTraceRecord(
            request: $request,
            response: $response,
            exception: null,
            durationMs: $this->durationMs($startedAt),
        );

        return $response;
    }

    /**
     * Возвращает все диагностические записи.
     *
     * @return list<HttpTraceRecord> Диагностические записи.
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * Возвращает последнюю диагностическую запись.
     *
     * @return HttpTraceRecord|null Последняя запись или null.
     */
    public function getLastRecord(): ?HttpTraceRecord
    {
        if ($this->records === []) {
            return null;
        }

        return $this->records[array_key_last($this->records)];
    }

    /**
     * Очищает накопленные диагностические записи.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->records = [];
    }

    /**
     * Считает длительность вызова в миллисекундах.
     *
     * @param int $startedAt Значение hrtime(true) на старте вызова.
     *
     * @return float Длительность в миллисекундах.
     */
    private function durationMs(int $startedAt): float
    {
        return (hrtime(true) - $startedAt) / 1_000_000;
    }
}
