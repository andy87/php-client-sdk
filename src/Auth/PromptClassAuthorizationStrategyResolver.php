<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Auth;

use and_y87\PhpClientSdk\Contracts\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\AuthorizationStrategyResolverInterface;
use and_y87\PhpClientSdk\Contracts\PromptInterface;

/**
 * Выбирает стратегию авторизации по классу Prompt DTO.
 */
class PromptClassAuthorizationStrategyResolver implements AuthorizationStrategyResolverInterface
{
    /** @var array<class-string<PromptInterface>, AuthorizationStrategyInterface> */
    private array $strategies = [];

    /**
     * Создаёт resolver стратегий авторизации.
     *
     * @param array<class-string<PromptInterface>, AuthorizationStrategyInterface> $strategies Стратегии по классам Prompt DTO.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если карта содержит некорректный класс Prompt DTO.
     */
    public function __construct(array $strategies = [])
    {
        foreach ($strategies as $promptClass => $strategy) {
            $this->add($promptClass, $strategy);
        }
    }

    /**
     * Добавляет стратегию авторизации для класса Prompt DTO.
     *
     * @param string $promptClass Класс Prompt DTO.
     * @param AuthorizationStrategyInterface $strategy Стратегия авторизации.
     *
     * @return static Текущий resolver.
     *
     * @throws \InvalidArgumentException Если класс Prompt DTO некорректен.
     */
    public function add(string $promptClass, AuthorizationStrategyInterface $strategy): static
    {
        if (!class_exists($promptClass)) {
            throw new \InvalidArgumentException(sprintf('Prompt class "%s" must exist.', $promptClass));
        }

        if (!is_a($promptClass, PromptInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Prompt class "%s" must implement %s.',
                $promptClass,
                PromptInterface::class,
            ));
        }

        $this->strategies[$promptClass] = $strategy;

        return $this;
    }

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
    ): ?AuthorizationStrategyInterface {
        return $this->strategies[$prompt::class] ?? null;
    }
}
