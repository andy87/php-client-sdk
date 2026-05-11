<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Client\Provider;

use and_y87\PhpClientSdk\Client\Config\ClientOptions;
use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationQueryStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;
use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;
use and_y87\PhpClientSdk\Contracts\Auth\RefreshableAuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Response\ResponseInterface;
use and_y87\PhpClientSdk\Client\Event\AfterRequestEvent;
use and_y87\PhpClientSdk\Client\Event\BeforeRequestEvent;
use and_y87\PhpClientSdk\Client\Event\ClientEvents;
use and_y87\PhpClientSdk\Client\Event\RequestExceptionEvent;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;
use and_y87\PhpClientSdk\Transport\Http\HttpResponse;
use and_y87\PhpClientSdk\Exception\ResponseHydrationException;
use and_y87\PhpClientSdk\Client\Runtime\ClientRuntime;

/**
 * Базовый provider для вызова API-методов через Prompt и Response DTO.
 */
abstract class AbstractProvider
{
    /** @var ClientRuntime Runtime-контекст клиента. */
    protected ClientRuntime $runtime;

    /** @var ClientOptions Настройки выполнения запросов. */
    protected ClientOptions $options;

    /** @var string Базовый URL API. */
    protected string $baseUrl;

    /**
     * Создаёт provider.
     *
     * @param string|\Stringable $baseUrl Базовый URL API.
     * @param AuthorizationStrategyInterface $authorizationStrategy Стратегия авторизации.
     * @param HttpTransportInterface $transport HTTP-транспорт.
     * @param int $timeout Таймаут запросов.
     * @param ClientRuntime|null $runtime Runtime-контекст клиента.
     * @param ClientOptions|null $options Настройки выполнения запросов.
     *
     * @return void
     */
    public function __construct(
        string|\Stringable $baseUrl,
        protected AuthorizationStrategyInterface $authorizationStrategy,
        protected HttpTransportInterface $transport,
        protected int $timeout = 30,
        ?ClientRuntime $runtime = null,
        ?ClientOptions $options = null,
    ) {
        $this->baseUrl = (string) $baseUrl;
        $this->options = $options ?? new ClientOptions(timeout: $timeout);
        $this->timeout = $this->options->timeout;
        $this->runtime = $runtime ?? new ClientRuntime($this->options->headers, $this->options->events);
    }

    /**
     * Отправляет запрос и возвращает DTO ответа.
     *
     * @template T of ResponseInterface
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string<T> $responseClass Класс DTO ответа.
     *
     * @return T DTO ответа.
     *
     * @throws \InvalidArgumentException Если Prompt или класс ответа невалиден.
     * @throws \RuntimeException Если HTTP-транспорт, декодирование или гидрация завершились ошибкой.
     */
    protected function request(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        if (!is_a($responseClass, ResponseInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('Response class "%s" must implement %s.', $responseClass, ResponseInterface::class));
        }

        if ($this->options->validatePrompt) {
            $prompt->validate();
        }

        $authorizationStrategy = $this->resolveAuthorizationStrategy($prompt);
        $httpRequest = $this->createHttpRequest($prompt, $authorizationStrategy);

        $this->runtime->dispatch(ClientEvents::BEFORE_REQUEST, new BeforeRequestEvent($this, $prompt, $httpRequest));
        $httpRequest = $this->options->requestFinalizer->finalize($httpRequest);

        try {
            $httpResponse = $this->sendWithRetry($httpRequest);
            $httpRequest = $this->refreshAuthorizationAndRetryOnce($prompt, $authorizationStrategy, $httpRequest, $httpResponse);
            /** @var HttpResponse $httpResponse */
            $httpResponse = $httpRequest->metadata['lastHttpResponse'];
            $data = $this->options->responseDecoder->decode($httpResponse);
            $error = $httpResponse->statusCode >= 400 ? $this->options->errorFactory->create($httpResponse, $data) : null;

            try {
                $response = new $responseClass(
                    $data,
                    $error,
                    $httpResponse->statusCode,
                    $httpResponse->headers,
                    $httpResponse->body,
                    $data,
                    $httpRequest,
                    $this->options->strictValidation,
                );
                /** @phpstan-ignore-next-line Response DTO constructors can validate runtime payloads and throw. */
            } catch (\Throwable $exception) {
                throw new ResponseHydrationException(
                    sprintf('Response DTO "%s" hydration failed: %s', $responseClass, $exception->getMessage()),
                    0,
                    $exception,
                );
            }
        } catch (\Throwable $exception) {
            $this->runtime->dispatch(ClientEvents::REQUEST_EXCEPTION, new RequestExceptionEvent($this, $prompt, $httpRequest, $exception));

            throw $exception;
        }

        $this->runtime->dispatch(ClientEvents::AFTER_REQUEST, new AfterRequestEvent($this, $prompt, $httpRequest, $httpResponse, $response));

        return $response;
    }

    /**
     * Собирает HTTP-запрос с учётом авторизации.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param AuthorizationStrategyInterface $authorizationStrategy Стратегия авторизации.
     *
     * @return HttpRequest HTTP-запрос.
     */
    private function createHttpRequest(
        PromptInterface $prompt,
        AuthorizationStrategyInterface $authorizationStrategy,
    ): HttpRequest {
        $headers = $this->runtime->mergeHeaders(['Accept' => 'application/json'], $this->runtime->getHeaders());
        $extraQuery = [];

        if ($prompt->requiresAuthorization()) {
            $headers = $this->runtime->mergeHeaders($headers, $authorizationStrategy->getAuthorizationHeaders($this->transport));

            if ($authorizationStrategy instanceof AuthorizationQueryStrategyInterface) {
                $extraQuery = $authorizationStrategy->getAuthorizationQueryParameters($this->transport);
            }
        }

        return $this->options->requestFactory->create(
            prompt: $prompt,
            baseUrl: $this->baseUrl,
            headers: $headers,
            timeout: $this->timeout,
            extraQuery: $extraQuery,
        );
    }

    /**
     * Выбирает стратегию авторизации для Prompt DTO.
     *
     * @param PromptInterface $prompt DTO запроса.
     *
     * @return AuthorizationStrategyInterface Стратегия авторизации.
     */
    private function resolveAuthorizationStrategy(PromptInterface $prompt): AuthorizationStrategyInterface
    {
        return $this->options->authorizationResolver?->resolve($prompt, $this->authorizationStrategy)
            ?? $this->authorizationStrategy;
    }

    /**
     * Обновляет авторизацию по настраиваемому HTTP-статусу и повторяет запрос один раз.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param AuthorizationStrategyInterface $authorizationStrategy Стратегия авторизации.
     * @param HttpRequest $httpRequest Исходный HTTP-запрос.
     * @param HttpResponse $httpResponse Исходный HTTP-ответ.
     *
     * @return HttpRequest Финальный HTTP-запрос с lastHttpResponse в metadata.
     */
    private function refreshAuthorizationAndRetryOnce(
        PromptInterface $prompt,
        AuthorizationStrategyInterface $authorizationStrategy,
        HttpRequest $httpRequest,
        HttpResponse $httpResponse,
    ): HttpRequest {
        $httpRequest->metadata['lastHttpResponse'] = $httpResponse;

        if (!$prompt->requiresAuthorization()) {
            return $httpRequest;
        }

        if (!$authorizationStrategy instanceof RefreshableAuthorizationStrategyInterface) {
            return $httpRequest;
        }

        if (!in_array($httpResponse->statusCode, $this->options->refreshAuthorizationStatusCodes, true)) {
            return $httpRequest;
        }

        $authorizationStrategy->refreshAuthorization($this->transport);

        $nextRequest = $this->createHttpRequest($prompt, $authorizationStrategy);
        $nextRequest->metadata['authorizationRefreshed'] = true;
        $this->runtime->dispatch(ClientEvents::BEFORE_REQUEST, new BeforeRequestEvent($this, $prompt, $nextRequest));
        $nextRequest = $this->options->requestFinalizer->finalize($nextRequest);
        $nextRequest->metadata['lastHttpResponse'] = $this->sendWithRetry($nextRequest);

        return $nextRequest;
    }

    /**
     * Отправляет HTTP-запрос с учётом retry policy.
     *
     * @param HttpRequest $httpRequest HTTP-запрос.
     *
     * @return HttpResponse HTTP-ответ.
     *
     * @throws \Throwable Если транспорт завершился ошибкой и retry policy не требует повтора.
     */
    protected function sendWithRetry(HttpRequest $httpRequest): HttpResponse
    {
        $attempt = 0;

        while (true) {
            ++$attempt;
            $httpRequest->metadata['attempt'] = $attempt;

            try {
                $response = $this->transport->send($httpRequest);
            } catch (\Throwable $exception) {
                if (!$this->options->retryPolicy->shouldRetry($attempt, $httpRequest, null, $exception)) {
                    throw $exception;
                }

                $this->waitBeforeRetry($this->options->retryPolicy->getDelayMs($attempt, $httpRequest, null, $exception));
                continue;
            }

            if (!$this->options->retryPolicy->shouldRetry($attempt, $httpRequest, $response)) {
                $httpRequest->metadata['attempts'] = $attempt;

                return $response;
            }

            $this->waitBeforeRetry($this->options->retryPolicy->getDelayMs($attempt, $httpRequest, $response));
        }
    }

    /**
     * Выполняет задержку перед повторной попыткой.
     *
     * @param int $delayMs Задержка в миллисекундах.
     *
     * @return void
     */
    protected function waitBeforeRetry(int $delayMs): void
    {
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
