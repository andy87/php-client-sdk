# Contracts Encoding

Папка содержит контракты кодирования query-параметров и тела HTTP-запроса.

## QueryEncoderInterface

`QueryEncoderInterface` превращает массив query-параметров в строку без ведущего знака `?`.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Encoding\QueryEncoderInterface;

final class SemicolonQueryEncoder implements QueryEncoderInterface
{
    public function encode(array $query): string
    {
        // Пример нестандартного разделителя параметров.
        return http_build_query($query, '', ';', PHP_QUERY_RFC3986);
    }
}
```

## BodyEncoderInterface

`BodyEncoderInterface` кодирует тело запроса и возвращает `HttpBody` с raw-содержимым, Content-Type и дополнительными заголовками.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Encoding\BodyEncoderInterface;
use and_y87\PhpClientSdk\Transport\Http\HttpBody;

final class TextBodyEncoder implements BodyEncoderInterface
{
    public function encode(array|string|null $body, ?string $contentType): HttpBody
    {
        // Пример кодировщика для text/plain.
        return new HttpBody(
            content: is_string($body) ? $body : '',
            contentType: $contentType ?? 'text/plain',
        );
    }
}
```
