<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Request\Finalizer;

use and_y87\PhpClientSdk\Contracts\Encoding\BodyEncoderInterface;
use and_y87\PhpClientSdk\Contracts\Encoding\QueryEncoderInterface;
use and_y87\PhpClientSdk\Contracts\Request\RequestFinalizerInterface;
use and_y87\PhpClientSdk\Request\Encoder\Body\DefaultBodyEncoder;
use and_y87\PhpClientSdk\Request\Encoder\Query\DefaultQueryEncoder;
use and_y87\PhpClientSdk\Transport\Http\HeaderUtils;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;

/**
 * Финализирует производные данные HTTP-запроса после пользовательских изменений.
 */
class DefaultRequestFinalizer implements RequestFinalizerInterface
{
    /**
     * Создаёт финализатор HTTP-запроса.
     *
     * @param QueryEncoderInterface $queryEncoder Кодировщик query-параметров.
     * @param BodyEncoderInterface $bodyEncoder Кодировщик тела запроса.
     *
     * @return void
     */
    public function __construct(
        private QueryEncoderInterface $queryEncoder = new DefaultQueryEncoder(),
        private BodyEncoderInterface $bodyEncoder = new DefaultBodyEncoder(),
    ) {}

    /**
     * Пересчитывает encoded query-string, raw body и заголовки по текущему состоянию запроса.
     *
     * @param HttpRequest $request HTTP-запрос после пользовательских изменений.
     *
     * @return HttpRequest Тот же HTTP-запрос с актуальной metadata.
     *
     * @throws \JsonException Если тело запроса нельзя закодировать в JSON.
     * @throws \InvalidArgumentException Если тело или заголовки некорректны.
     */
    public function finalize(HttpRequest $request): HttpRequest
    {
        $request->metadata['queryString'] = $this->encodeQuery(
            $request->query,
            is_array($request->metadata['queryParameterStyles'] ?? null) ? $request->metadata['queryParameterStyles'] : [],
        );
        $request->headers = HeaderUtils::merge([], $request->headers);

        if ($request->body !== null) {
            $encodedBody = $this->bodyEncoder->encode($request->body, $request->contentType);

            $request->rawBody = $encodedBody->content;
            $request->contentType = $encodedBody->contentType ?? $request->contentType;
            $request->headers = HeaderUtils::merge($request->headers, $encodedBody->headers);

            if ($encodedBody->contentType !== null) {
                $request->headers = HeaderUtils::merge($request->headers, ['Content-Type' => $encodedBody->contentType]);
            }
        } else {
            $request->rawBody = null;

            if ($request->contentType !== null && !HeaderUtils::has($request->headers, 'Content-Type')) {
                $request->headers = HeaderUtils::merge($request->headers, ['Content-Type' => $request->contentType]);
            }
        }

        return $request;
    }

    /**
     * Кодирует query-параметры с учётом OpenAPI style/explode, если encoder это поддерживает.
     *
     * @param array<string, mixed> $query Query-параметры.
     * @param array<string, mixed> $styles Правила кодирования по API-именам.
     *
     * @return string Query-string или пустая строка.
     */
    private function encodeQuery(array $query, array $styles): string
    {
        if (method_exists($this->queryEncoder, 'encodeWithStyles')) {
            /** @var callable(array<string, mixed>, array<string, mixed>): string $encoder */
            $encoder = [$this->queryEncoder, 'encodeWithStyles'];

            return $encoder($query, $styles);
        }

        return $this->queryEncoder->encode($query);
    }
}
