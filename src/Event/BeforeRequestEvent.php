<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Event;

use and_y87\PhpClientSdk\Contracts\PromptInterface;
use and_y87\PhpClientSdk\Http\HttpRequest;
use and_y87\PhpClientSdk\Provider\AbstractProvider;

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
