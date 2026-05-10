<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Config;

use and_y87\PhpClientSdk\Contracts\ApiErrorFactoryInterface;
use and_y87\PhpClientSdk\Contracts\AuthorizationStrategyResolverInterface;
use and_y87\PhpClientSdk\Contracts\BodyEncoderInterface;
use and_y87\PhpClientSdk\Contracts\QueryEncoderInterface;
use and_y87\PhpClientSdk\Contracts\RequestFactoryInterface;
use and_y87\PhpClientSdk\Contracts\RequestFinalizerInterface;
use and_y87\PhpClientSdk\Contracts\ResponseDecoderInterface;
use and_y87\PhpClientSdk\Contracts\RetryPolicyInterface;
use and_y87\PhpClientSdk\Decoder\JsonResponseDecoder;
use and_y87\PhpClientSdk\Encoder\DefaultBodyEncoder;
use and_y87\PhpClientSdk\Encoder\DefaultQueryEncoder;
use and_y87\PhpClientSdk\Error\DefaultApiErrorFactory;
use and_y87\PhpClientSdk\Http\HeaderUtils;
use and_y87\PhpClientSdk\Request\DefaultRequestFinalizer;
use and_y87\PhpClientSdk\Request\DefaultRequestFactory;
use and_y87\PhpClientSdk\Retry\NoRetryPolicy;

/**
 * Хранит настраиваемые параметры выполнения API-запросов.
 */
class ClientOptions
{
    /**
     * Создаёт настройки клиента.
     *
     * @param int $timeout Таймаут запросов в секундах.
     * @param array<string, string> $headers Дефолтные заголовки.
     * @param array<string, callable|list<callable>> $events Обработчики событий.
     * @param bool $strictValidation Проверять обязательные поля успешных ответов.
     * @param RetryPolicyInterface|null $retryPolicy Retry policy.
     * @param QueryEncoderInterface|null $queryEncoder Кодировщик query-параметров.
     * @param BodyEncoderInterface|null $bodyEncoder Кодировщик тела запроса.
     * @param ResponseDecoderInterface|null $responseDecoder Декодер ответа.
     * @param ApiErrorFactoryInterface|null $errorFactory Фабрика ошибок API.
     * @param RequestFactoryInterface|null $requestFactory Фабрика HTTP-запросов.
     * @param RequestFinalizerInterface|null $requestFinalizer Финализатор HTTP-запроса.
     * @param bool $validatePrompt Проверять обязательные поля Prompt DTO перед запросом.
     * @param AuthorizationStrategyResolverInterface|null $authorizationResolver Resolver стратегии авторизации по Prompt DTO.
     * @param list<int> $refreshAuthorizationStatusCodes HTTP-статусы для принудительного обновления авторизации и одного повтора запроса.
     *
     * @return void
     */
    public function __construct(
        public int $timeout = 30,
        public array $headers = [],
        public array $events = [],
        public bool $strictValidation = true,
        public ?RetryPolicyInterface $retryPolicy = null,
        public ?QueryEncoderInterface $queryEncoder = null,
        public ?BodyEncoderInterface $bodyEncoder = null,
        public ?ResponseDecoderInterface $responseDecoder = null,
        public ?ApiErrorFactoryInterface $errorFactory = null,
        public ?RequestFactoryInterface $requestFactory = null,
        public ?RequestFinalizerInterface $requestFinalizer = null,
        public bool $validatePrompt = true,
        public ?AuthorizationStrategyResolverInterface $authorizationResolver = null,
        public array $refreshAuthorizationStatusCodes = [401],
    ) {
        $this->headers = HeaderUtils::merge([], $this->headers);
        $this->retryPolicy ??= new NoRetryPolicy();
        $this->queryEncoder ??= new DefaultQueryEncoder();
        $this->bodyEncoder ??= new DefaultBodyEncoder();
        $this->responseDecoder ??= new JsonResponseDecoder();
        $this->errorFactory ??= new DefaultApiErrorFactory();
        $this->requestFactory ??= new DefaultRequestFactory($this->queryEncoder, $this->bodyEncoder);
        $this->requestFinalizer ??= new DefaultRequestFinalizer($this->queryEncoder, $this->bodyEncoder);
    }
}
