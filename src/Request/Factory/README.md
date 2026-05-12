# Request Factory

Папка содержит фабрику, которая превращает Prompt DTO в `HttpRequest`.

## DefaultRequestFactory

`DefaultRequestFactory` подставляет path-параметры в endpoint, объединяет query-параметры, кодирует body и переносит служебные данные в `metadata`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Factory\DefaultRequestFactory;
use and_y87\PhpClientSdk\Request\Prompt\PublicPrompt;

final class GetUserPrompt extends PublicPrompt
{
    protected const METHOD = 'GET';
    protected const ENDPOINT = '/users/{id}';
    protected const FIELD_MAP = ['id' => 'id', 'withPosts' => 'with_posts'];
    protected const REQUIRED_FIELDS = ['id'];
    protected const PATH_FIELDS = ['id'];
    protected const QUERY_FIELDS = ['withPosts'];

    public int $id;
    public bool $withPosts = false;
}

$request = (new DefaultRequestFactory())->create(
    prompt: new GetUserPrompt(['id' => 7, 'withPosts' => true]),
    baseUrl: 'https://api.example.com',
    headers: ['Accept' => 'application/json'],
    timeout: 30,
);

echo $request->url; // https://api.example.com/users/7
echo $request->metadata['queryString']; // with_posts=true
```
