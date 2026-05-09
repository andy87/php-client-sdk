<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Request;

use Andy87\ClientsBase\Contracts\BodyEncoderInterface;
use Andy87\ClientsBase\Contracts\PromptInterface;
use Andy87\ClientsBase\Contracts\QueryEncoderInterface;
use Andy87\ClientsBase\Contracts\RequestFactoryInterface;
use Andy87\ClientsBase\Encoder\DefaultBodyEncoder;
use Andy87\ClientsBase\Encoder\DefaultQueryEncoder;
use Andy87\ClientsBase\Exception\ValidationException;
use Andy87\ClientsBase\Http\HeaderUtils;
use Andy87\ClientsBase\Http\HttpRequest;

/**
 * Создаёт HTTP-запрос из Prompt DTO.
 */
class DefaultRequestFactory implements RequestFactoryInterface
{
    /**
     * Создаёт фабрику HTTP-запросов.
     *
     * @param QueryEncoderInterface $queryEncoder Кодировщик query-параметров.
     * @param BodyEncoderInterface $bodyEncoder Кодировщик тела запроса.
     *
     * @return void
     */
    public function __construct(
        private QueryEncoderInterface $queryEncoder = new DefaultQueryEncoder(),
        private BodyEncoderInterface $bodyEncoder = new DefaultBodyEncoder(),
    ) {
    }

    /**
     * Собирает HTTP-запрос для транспорта.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param string $baseUrl Базовый URL API.
     * @param array<string, string> $headers Заголовки запроса.
     * @param int $timeout Таймаут запроса в секундах.
     * @param array<string, mixed> $extraQuery Дополнительные query-параметры.
     *
     * @return HttpRequest HTTP-запрос.
     *
     * @throws ValidationException Если endpoint содержит незаполненные path-плейсхолдеры.
     * @throws \JsonException Если body нельзя закодировать в JSON.
     */
    public function create(
        PromptInterface $prompt,
        string $baseUrl,
        array $headers,
        int $timeout,
        array $extraQuery = [],
    ): HttpRequest {
        $endpoint = $this->buildEndpoint($prompt);
        $query = array_merge($prompt->getQueryParameters(), $extraQuery);
        $queryStyles = $this->getQueryParameterStyles($prompt);
        $queryString = $this->encodeQuery($query, $queryStyles);
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $body = $prompt->getBody();
        $headers = HeaderUtils::merge($headers, $prompt->getHeaderParameters());

        $encodedBody = $this->bodyEncoder->encode($body, $prompt->getContentType());
        $headers = HeaderUtils::merge($headers, $encodedBody->headers);

        if ($encodedBody->contentType !== null && !HeaderUtils::has($headers, 'Content-Type')) {
            $headers = HeaderUtils::merge($headers, ['Content-Type' => $encodedBody->contentType]);
        }

        return new HttpRequest(
            method: $prompt->getMethod(),
            url: $url,
            headers: $headers,
            query: $query,
            body: $body,
            contentType: $encodedBody->contentType ?? $prompt->getContentType(),
            timeout: $timeout,
            rawBody: $encodedBody->content,
            metadata: [
                'queryString' => $queryString,
                'queryParameterStyles' => $queryStyles,
                'promptClass' => $prompt::class,
                'endpoint' => $prompt->getEndpoint(),
            ],
        );
    }

    /**
     * Собирает endpoint с path-параметрами.
     *
     * @param PromptInterface $prompt DTO запроса.
     *
     * @return string Endpoint.
     *
     * @throws ValidationException Если endpoint содержит незаполненные path-плейсхолдеры.
     */
    private function buildEndpoint(PromptInterface $prompt): string
    {
        $endpoint = $prompt->getEndpoint();

        foreach ($prompt->getPathParameters() as $name => $value) {
            $endpoint = str_replace('{' . $name . '}', rawurlencode((string) $value), $endpoint);
        }

        if (preg_match('/\{[^}]+\}/', $endpoint, $matches) === 1) {
            throw new ValidationException(sprintf('Endpoint path parameter "%s" is not filled.', $matches[0]));
        }

        return $endpoint;
    }

    /**
     * Кодирует query-параметры с учётом OpenAPI style/explode, если encoder это поддерживает.
     *
     * @param array<string, mixed> $query Query-параметры.
     * @param array<string, array{style?:string,explode?:bool}> $styles Правила кодирования по API-именам.
     *
     * @return string Query-string или пустая строка.
     */
    private function encodeQuery(array $query, array $styles): string
    {
        if (method_exists($this->queryEncoder, 'encodeWithStyles')) {
            /** @var callable(array<string, mixed>, array<string, array{style?:string,explode?:bool}>): string $encoder */
            $encoder = [$this->queryEncoder, 'encodeWithStyles'];

            return $encoder($query, $styles);
        }

        return $this->queryEncoder->encode($query);
    }

    /**
     * Возвращает OpenAPI-правила кодирования query-параметров из Prompt DTO.
     *
     * @param PromptInterface $prompt DTO запроса.
     *
     * @return array<string, array{style?:string,explode?:bool}> Правила по API-именам query-параметров.
     */
    private function getQueryParameterStyles(PromptInterface $prompt): array
    {
        if (!method_exists($prompt, 'getQueryParameterStyles')) {
            return [];
        }

        $styles = $prompt->getQueryParameterStyles();

        return is_array($styles) ? $styles : [];
    }

}
