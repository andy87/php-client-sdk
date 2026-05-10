<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Contracts;

use and_y87\PhpClientSdk\Http\HttpBody;

/**
 * Кодирует тело HTTP-запроса перед отправкой транспортом.
 */
interface BodyEncoderInterface
{
    /**
     * Кодирует тело запроса.
     *
     * @param array<string, mixed>|list<mixed>|string|null $body Тело запроса.
     * @param string|null $contentType Желаемый Content-Type.
     *
     * @return HttpBody Закодированное тело и дополнительные заголовки.
     *
     * @throws \JsonException Если JSON-кодирование завершилось ошибкой.
     * @throws \InvalidArgumentException Если тело нельзя закодировать выбранным способом.
     */
    public function encode(array|string|null $body, ?string $contentType): HttpBody;
}
