<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Security\Authorization\Resolver;

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyResolverInterface;
use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;

/**
 * Выбирает стратегию авторизации через пользовательский callback.
 */
class CallbackAuthorizationStrategyResolver implements AuthorizationStrategyResolverInterface
{
    /** @var \Closure */
    private \Closure $callback;

    /**
     * Создаёт callback resolver стратегий авторизации.
     *
     * @param callable $callback Callback вида fn(PromptInterface $prompt, AuthorizationStrategyInterface $defaultStrategy): ?AuthorizationStrategyInterface.
     *
     * @return void
     */
    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    /**
     * Возвращает стратегию авторизации для Prompt DTO.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param AuthorizationStrategyInterface $defaultStrategy Стратегия по умолчанию.
     *
     * @return AuthorizationStrategyInterface|null Стратегия авторизации или null для стратегии по умолчанию.
     *
     * @throws \UnexpectedValueException Если callback вернул некорректный тип.
     */
    public function resolve(
        PromptInterface $prompt,
        AuthorizationStrategyInterface $defaultStrategy,
    ): ?AuthorizationStrategyInterface {
        $strategy = ($this->callback)($prompt, $defaultStrategy);

        if ($strategy === null || $strategy instanceof AuthorizationStrategyInterface) {
            return $strategy;
        }

        throw new \UnexpectedValueException('Authorization strategy resolver must return AuthorizationStrategyInterface or null.');
    }
}
