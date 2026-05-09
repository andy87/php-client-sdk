<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Contracts;

/**
 * Описывает Prompt DTO с логическим профилем авторизации.
 */
interface AuthorizationProfilePromptInterface
{
    /**
     * Возвращает логический профиль авторизации Prompt DTO.
     *
     * @return string Профиль авторизации.
     */
    public function getAuthorizationProfile(): string;
}
