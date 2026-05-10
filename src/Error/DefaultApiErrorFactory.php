<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Error;

use and_y87\PhpClientSdk\Contracts\ApiErrorFactoryInterface;
use and_y87\PhpClientSdk\Dto\ApiError;
use and_y87\PhpClientSdk\Http\HttpResponse;

/**
 * Создаёт ApiError из распространённых форматов ошибок API.
 */
class DefaultApiErrorFactory implements ApiErrorFactoryInterface
{
    /**
     * Создаёт DTO ошибки API.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     * @param array<string, mixed>|list<mixed> $decodedBody Декодированное тело ответа.
     *
     * @return ApiError DTO ошибки API.
     */
    public function create(HttpResponse $response, array $decodedBody): ApiError
    {
        return new ApiError(
            raw: $decodedBody,
            statusCode: $response->statusCode,
            headers: $response->headers,
            rawBody: $response->body,
        );
    }
}
