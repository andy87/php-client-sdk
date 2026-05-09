<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Event;

use Andy87\PhpClientSdk\Contracts\PromptInterface;
use Andy87\PhpClientSdk\Http\HttpRequest;
use Andy87\PhpClientSdk\Provider\AbstractProvider;

/**
 * Описывает событие перед отправкой HTTP-запроса.
 */
class BeforeRequestEvent
{
    /**
     * Создаёт событие перед отправкой HTTP-запроса.
     *
     * @param AbstractProvider $provider Provider, выполняющий запрос.
     * @param PromptInterface $prompt DTO запроса.
     * @param HttpRequest $request Mutable HTTP-запрос перед отправкой.
     *
     * @return void
     */
    public function __construct(
        public AbstractProvider $provider,
        public PromptInterface $prompt,
        public HttpRequest $request,
    ) {
    }
}
