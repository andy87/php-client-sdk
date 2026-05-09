<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Decoder;

use Andy87\PhpClientSdk\Contracts\ResponseDecoderInterface;
use Andy87\PhpClientSdk\Exception\ResponseDecodeException;
use Andy87\PhpClientSdk\Http\HeaderUtils;
use Andy87\PhpClientSdk\Http\HttpResponse;

/**
 * Декодирует JSON-ответы API.
 */
class JsonResponseDecoder implements ResponseDecoderInterface
{
    /**
     * Декодирует JSON-тело ответа.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     *
     * @return array<string, mixed>|list<mixed> Декодированное тело ответа.
     *
     * @throws ResponseDecodeException Если успешный ответ не является JSON-объектом или массивом.
     */
    public function decode(HttpResponse $response): array
    {
        if ($response->body === '') {
            return [];
        }

        if (!$this->isJsonResponse($response)) {
            return [];
        }

        try {
            $data = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            if ($response->statusCode >= 400) {
                return [];
            }

            throw new ResponseDecodeException('API returned invalid JSON response.', 0, $exception);
        }

        if (!is_array($data)) {
            if ($response->statusCode >= 400) {
                return [];
            }

            throw new ResponseDecodeException('API returned non-object JSON response.');
        }

        return $data;
    }

    /**
     * Проверяет, что ответ заявлен как JSON.
     *
     * @param HttpResponse $response Raw HTTP-ответ.
     *
     * @return bool true, если Content-Type является JSON или отсутствует.
     */
    private function isJsonResponse(HttpResponse $response): bool
    {
        $contentType = null;

        foreach ($response->headers as $name => $value) {
            if (strtolower(HeaderUtils::normalizeName($name)) === 'content-type') {
                $contentType = HeaderUtils::normalizeValue($value);
                break;
            }
        }

        if ($contentType === null || trim($contentType) === '') {
            return true;
        }

        $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));

        return $mediaType === 'application/json' || str_ends_with($mediaType, '+json');
    }
}
