<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Event;

use and_y87\PhpClientSdk\Contracts\PromptInterface;
use and_y87\PhpClientSdk\Http\HttpRequest;
use and_y87\PhpClientSdk\Provider\AbstractProvider;

/**
 * Описывает событие ошибки во время выполнения HTTP-запроса.
 */
class RequestExceptionEvent
{
    /**
     * Создаёт событие ошибки во время выполнения HTTP-запроса.
     *
     * @param AbstractProvider $provider Provider, выполнявший запрос.
     * @param PromptInterface $prompt DTO запроса.
     * @param HttpRequest|null $request HTTP-запрос, если он был собран до ошибки.
     * @param \Throwable $exception Исключение, которое будет проброшено наружу.
     *
     * @return void
     */
    public function __construct(
        public AbstractProvider $provider,
        public PromptInterface $prompt,
        public ?HttpRequest $request,
        public \Throwable $exception,
    ) {
    }
}
