# Request Encoder

Папка содержит кодировщики query-параметров и тела HTTP-запроса.

## DefaultBodyEncoder

`DefaultBodyEncoder` выбирает конкретный body encoder по `Content-Type`: JSON, form-urlencoded, multipart или raw string.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Encoder\Body\DefaultBodyEncoder;

$encoder = new DefaultBodyEncoder();

$body = $encoder->encode(
    body: ['name' => 'Alice'],
    contentType: 'application/json',
);

echo $body->content; // {"name":"Alice"}
```

## JsonBodyEncoder

`JsonBodyEncoder` кодирует массив в JSON или принимает готовую JSON-строку.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Encoder\Body\JsonBodyEncoder;

$body = (new JsonBodyEncoder())->encode(['active' => true], null);

echo $body->contentType; // application/json
```

## FormBodyEncoder

`FormBodyEncoder` кодирует массив в `application/x-www-form-urlencoded`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Encoder\Body\FormBodyEncoder;

$body = (new FormBodyEncoder())->encode(['page' => 1, 'q' => 'test'], null);

echo $body->content; // page=1&q=test
```

## MultipartBodyEncoder

`MultipartBodyEncoder` собирает `multipart/form-data`, включая файлы через `MultipartFile`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Encoder\Body\MultipartBodyEncoder;
use and_y87\PhpClientSdk\Transport\Http\MultipartFile;

$body = (new MultipartBodyEncoder())->encode([
    'title' => 'Avatar',
    'file' => new MultipartFile('/tmp/avatar.png', 'avatar.png', 'image/png'),
], 'multipart/form-data');

// Content-Type будет содержать автоматически созданный boundary.
echo $body->contentType;
```

## DefaultQueryEncoder

`DefaultQueryEncoder` кодирует query-параметры по RFC 3986 и умеет учитывать OpenAPI `style/explode` через `encodeWithStyles()`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Request\Encoder\Query\DefaultQueryEncoder;

$encoder = new DefaultQueryEncoder();

echo $encoder->encode([
    'filter' => 'active users',
    'page' => 2,
]); // filter=active%20users&page=2

echo $encoder->encodeWithStyles(
    ['ids' => [10, 20]],
    ['ids' => ['style' => 'form', 'explode' => false]],
); // ids=10%2C20
```
