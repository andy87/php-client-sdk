<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Request\Prompt;

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationProfilePromptInterface;

/**
 * Базовый Prompt DTO для приватных API endpoints с профилем авторизации.
 */
abstract class PrivatePrompt extends AbstractPrompt implements AuthorizationProfilePromptInterface
{
    /** @var bool Приватный endpoint требует авторизацию. */
    protected const AUTHORIZATION_REQUIRED = true;

    /** @var string Логический профиль авторизации. */
    protected const AUTHORIZATION_PROFILE = 'default';

    /**
     * Возвращает логический профиль авторизации Prompt DTO.
     *
     * @return string Профиль авторизации.
     */
    public function getAuthorizationProfile(): string
    {
        return static::AUTHORIZATION_PROFILE;
    }
}
