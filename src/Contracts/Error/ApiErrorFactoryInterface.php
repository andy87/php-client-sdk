<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Contracts\Error;

use and_y87\PhpClientSdk\Response\Error\ApiError;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;

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
