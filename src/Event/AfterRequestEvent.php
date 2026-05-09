<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Event;

use Andy87\PhpClientSdk\Contracts\PromptInterface;
use Andy87\PhpClientSdk\Contracts\ResponseInterface;
use Andy87\PhpClientSdk\Http\HttpRequest;
use Andy87\PhpClientSdk\Http\HttpResponse;
use Andy87\PhpClientSdk\Provider\AbstractProvider;

/**
 * Описывает событие после успешного получения и нормализации HTTP-ответа.
 */
class AfterRequestEvent
{
    /**
     * Создаёт событие после успешного получения и нормализации HTTP-ответа.
     *
     * @param AbstractProvider $provider Provider, выполнивший запрос.
     * @param PromptInterface $prompt DTO запроса.
     * @param HttpRequest $request Отправленный HTTP-запрос.
     * @param HttpResponse $httpResponse Raw HTTP-ответ транспорта.
     * @param ResponseInterface $response Типизированный DTO ответа.
     *
     * @return void
     */
    public function __construct(
        public AbstractProvider $provider,
        public PromptInterface $prompt,
        public HttpRequest $request,
        public HttpResponse $httpResponse,
        public ResponseInterface $response,
    ) {
    }
}
