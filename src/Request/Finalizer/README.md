# Request Finalizer

Папка содержит финализатор HTTP-запроса. Он нужен после пользовательских событий, потому что listener может изменить query, body или headers.

## DefaultRequestFinalizer

`DefaultRequestFinalizer` пересчитывает `metadata['queryString']`, нормализует заголовки и заново кодирует body.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Finalizer\DefaultRequestFinalizer;
use and_y87\PhpClientSdk\Transport\Http\HttpRequest;

$request = new HttpRequest(
    method: 'POST',
    url: 'https://api.example.com/users',
    headers: ['accept' => 'application/json'],
    query: ['notify' => true],
    body: ['name' => 'Alice'],
    contentType: 'application/json',
);

// Например, listener добавил query-параметр перед отправкой.
$request->query['source'] = 'sdk';

$finalized = (new DefaultRequestFinalizer())->finalize($request);

echo $finalized->metadata['queryString']; // notify=true&source=sdk
echo $finalized->rawBody; // {"name":"Alice"}
```
