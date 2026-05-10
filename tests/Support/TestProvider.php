<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests\Support;

use and_y87\PhpClientSdk\Contracts\PromptInterface;
use and_y87\PhpClientSdk\Contracts\ResponseInterface;
use and_y87\PhpClientSdk\Provider\AbstractProvider;

/**
 * Тестовый provider, открывающий protected request() для PHPUnit.
 */
class TestProvider extends AbstractProvider
{
    /**
     * Выполняет тестовый API-запрос.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string<ResponseInterface> $responseClass Класс DTO ответа.
     *
     * @return ResponseInterface DTO ответа.
     */
    public function call(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        return $this->request($prompt, $responseClass);
    }

    /**
     * Выполняет тестовый API-запрос с произвольным классом ответа для проверки runtime-контракта.
     *
     * @param PromptInterface $prompt DTO запроса.
     * @param class-string $responseClass Класс DTO ответа.
     *
     * @return ResponseInterface DTO ответа.
     */
    public function callAnyResponseClass(PromptInterface $prompt, string $responseClass): ResponseInterface
    {
        return $this->request($prompt, $responseClass);
    }
}
