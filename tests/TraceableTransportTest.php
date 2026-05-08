<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Tests;

use Andy87\ClientsBase\Http\HttpRequest;
use Andy87\ClientsBase\Http\HttpResponse;
use Andy87\ClientsBase\Http\TraceableTransport;
use Andy87\ClientsBase\Tests\Support\FakeTransport;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет диагностический transport-wrapper.
 */
class TraceableTransportTest extends TestCase
{
    /**
     * Проверяет, что успешный ответ возвращается без изменений и записывается в trace.
     *
     * @return void
     */
    public function testSendReturnsResponseAndRecordsTrace(): void
    {
        $request = new HttpRequest('GET', 'https://api.example.test/users/10');
        $response = new HttpResponse(200, ['X-Test' => '1'], '{"id":10}');
        $transport = new TraceableTransport(new FakeTransport([$response]));

        self::assertSame($response, $transport->send($request));

        $records = $transport->getRecords();

        self::assertCount(1, $records);
        self::assertSame($request, $records[0]->request);
        self::assertSame($response, $records[0]->response);
        self::assertNull($records[0]->exception);
        self::assertGreaterThanOrEqual(0.0, $records[0]->durationMs);
        self::assertSame($records[0], $transport->getLastRecord());
    }

    /**
     * Проверяет, что исключение транспорта записывается в trace и пробрасывается наружу.
     *
     * @return void
     */
    public function testSendRecordsExceptionAndRethrowsIt(): void
    {
        $request = new HttpRequest('GET', 'https://api.example.test/users/10');
        $exception = new \RuntimeException('Transport failed.');
        $transport = new TraceableTransport(new FakeTransport([$exception]));

        try {
            $transport->send($request);
            self::fail('Transport exception was not thrown.');
        } catch (\RuntimeException $thrown) {
            self::assertSame($exception, $thrown);
        }

        $record = $transport->getLastRecord();

        self::assertNotNull($record);
        self::assertSame($request, $record->request);
        self::assertNull($record->response);
        self::assertSame($exception, $record->exception);
        self::assertGreaterThanOrEqual(0.0, $record->durationMs);
    }

    /**
     * Проверяет очистку накопленных trace-записей.
     *
     * @return void
     */
    public function testClearRemovesRecords(): void
    {
        $transport = new TraceableTransport(new FakeTransport([
            new HttpResponse(200, [], '{}'),
        ]));

        $transport->send(new HttpRequest('GET', 'https://api.example.test/users/10'));
        $transport->clear();

        self::assertSame([], $transport->getRecords());
        self::assertNull($transport->getLastRecord());
    }
}
