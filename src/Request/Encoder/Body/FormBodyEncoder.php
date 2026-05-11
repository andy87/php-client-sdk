<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Request\Encoder\Body;

use and_y87\PhpClientSdk\Contracts\Encoding\BodyEncoderInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpBody;

/**
 * Кодирует тело HTTP-запроса в application/x-www-form-urlencoded.
 */
class FormBodyEncoder implements BodyEncoderInterface
{
    /**
     * Кодирует тело запроса в form-urlencoded строку.
     *
     * @param array<string, mixed>|list<mixed>|string|null $body Тело запроса.
     * @param string|null $contentType Желаемый Content-Type.
     *
     * @return HttpBody Закодированное тело.
     *
     * @throws \InvalidArgumentException Если тело не является массивом или строкой.
     */
    public function encode(array|string|null $body, ?string $contentType): HttpBody
    {
        if ($body === null) {
            return new HttpBody();
        }

        if (is_string($body)) {
            return new HttpBody($body, $contentType ?? 'application/x-www-form-urlencoded');
        }

        return new HttpBody(http_build_query($body), $contentType ?? 'application/x-www-form-urlencoded');
    }
}
