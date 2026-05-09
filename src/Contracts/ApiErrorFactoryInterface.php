<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Contracts;

use Andy87\PhpClientSdk\Dto\ApiError;
use Andy87\PhpClientSdk\Http\HttpResponse;

/**
 * Создаёт нормализованную ошибку API из HTTP-ответа.
 */
interface ApiErrorFactoryInterface
{
    /**
     * Создаёт DTO ошибки API.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     * @param array<string, mixed>|list<mixed> $decodedBody Декодированное тело ответа.
     *
     * @return ApiError DTO ошибки API.
     */
    public function create(HttpResponse $response, array $decodedBody): ApiError;
}
