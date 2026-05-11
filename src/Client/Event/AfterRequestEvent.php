<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Client\Event;

use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;
use and_y87\PhpClientSdk\Contracts\Response\ResponseInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;
use and_y87\PhpClientSdk\Client\Provider\AbstractProvider;

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
    ) {}
}
