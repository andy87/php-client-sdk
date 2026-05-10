<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Auth;

use and_y87\PhpClientSdk\Contracts\AuthorizationProfilePromptInterface;
use and_y87\PhpClientSdk\Contracts\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\AuthorizationStrategyResolverInterface;
use and_y87\PhpClientSdk\Contracts\PromptInterface;

/**
 * Выбирает стратегию авторизации по логическому профилю Prompt DTO.
 */
class AuthorizationProfileStrategyResolver implements AuthorizationStrategyResolverInterface
{
    /** @var array<string, AuthorizationStrategyInterface> Стратегии авторизации по профилю. */
    private array $strategies = [];

    /**
     * Создаёт resolver стратегий авторизации по профилю.
     *
     * @param array<string, AuthorizationStrategyInterface> $strategies Стратегии по профилям авторизации.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если profile пустой или strategy некорректна.
     */
    public function __construct(array $strategies = [])
    {
        foreach ($strategies as $profile => $strategy) {
            $this->add((string) $profile, $strategy);
        }
    }

    /**
     * Добавляет стратегию авторизации для профиля.
     *
     * @param string $profile Профиль авторизации.
     * @param AuthorizationStrategyInterface $strategy Стратегия авторизации.
     *
     * @return static Текущий resolver.
     *
     * @throws \InvalidArgumentException Если profile пустой.
     */
    public function add(string $profile, AuthorizationStrategyInterface $strategy): static
    {
        $profile = trim($profile);

        if ($profile === '') {
            throw new \InvalidArgumentException('Authorization profile must be a non-empty string.');
        }

        $this->strategies[$profile] = $strategy;

        return $this;
    }

    /**
     * Возвращает стратегию авторизации по профилю Prompt DTO.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param AuthorizationStrategyInterface $defaultStrategy Стратегия по умолчанию.
     *
     * @return AuthorizationStrategyInterface|null Стратегия авторизации или null для стратегии по умолчанию.
     */
    public function resolve(
        PromptInterface $prompt,
        AuthorizationStrategyInterface $defaultStrategy,
    ): ?AuthorizationStrategyInterface {
        if (!$prompt instanceof AuthorizationProfilePromptInterface) {
            return null;
        }

        return $this->strategies[$prompt->getAuthorizationProfile()] ?? null;
    }
}
