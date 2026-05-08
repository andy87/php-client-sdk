<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Contracts;

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
