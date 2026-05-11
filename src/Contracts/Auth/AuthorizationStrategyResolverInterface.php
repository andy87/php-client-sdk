<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Contracts\Auth;

use and_y87\PhpClientSdk\Contracts\Request\PromptInterface;

/**
 * Выбирает стратегию авторизации для конкретного Prompt DTO.
 */
interface AuthorizationStrategyResolverInterface
{
    /**
     * Возвращает стратегию авторизации для Prompt DTO.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param AuthorizationStrategyInterface $defaultStrategy Стратегия по умолчанию.
     *
     * @return AuthorizationStrategyInterface|null Стратегия авторизации или null для стратегии по умолчанию.
     */
    public function resolve(
        PromptInterface $prompt,
        AuthorizationStrategyInterface $defaultStrategy,
    ): ?AuthorizationStrategyInterface;
}
