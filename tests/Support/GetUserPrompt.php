<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Tests\Support;

use and_y87\PhpClientSdk\Prompt\AbstractPrompt;

/**
 * Тестовый Prompt DTO для проверки сборки запросов.
 */
class GetUserPrompt extends AbstractPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/users/{id}';
    protected const FIELD_MAP = ['id' => 'id', 'includePosts' => 'include_posts'];
    protected const REQUIRED_FIELDS = ['id'];
    protected const PATH_FIELDS = ['id'];
    protected const QUERY_FIELDS = ['includePosts'];
    protected const BODY_FIELDS = [];

    public int $id;
    public ?bool $includePosts = null;
}
