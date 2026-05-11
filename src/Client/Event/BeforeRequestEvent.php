<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Client\Event;

use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Client\Provider\AbstractProvider;

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
    ) {}
}
