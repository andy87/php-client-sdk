<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Prompt;

/**
 * Базовый Prompt DTO для публичных API endpoints без авторизации.
 */
abstract class PublicPrompt extends AbstractPrompt
{
    /** @var bool Публичный endpoint не требует авторизацию. */
    protected const AUTHORIZATION_REQUIRED = false;
}
