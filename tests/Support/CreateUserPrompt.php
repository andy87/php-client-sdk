<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests\Support;

use and_y87\PhpClientSdk\Request\Prompt\AbstractPrompt;

/**
 * Тестовый Prompt DTO для проверки кодирования тела запроса.
 */
class CreateUserPrompt extends AbstractPrompt
{
    protected const METHOD = 'POST';
    protected const ENDPOINT = '/users';
    protected const CONTENT_TYPE = 'application/json';
    protected const FIELD_MAP = ['name' => 'name', 'requestSource' => 'X-Request-Source'];
    protected const REQUIRED_FIELDS = ['name'];
    protected const HEADER_FIELDS = ['requestSource'];
    protected const BODY_FIELDS = ['name'];

    public string $name;
    public ?string $requestSource = null;
}
