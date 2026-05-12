# Contracts Cache

Папка содержит контракт TTL-кеша, который используется, например, для хранения OAuth token.

## CacheInterface

`CacheInterface` задаёт минимальный набор операций: чтение, запись с TTL и удаление.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Contracts\Cache\CacheInterface;

final class MemoryCache implements CacheInterface
{
    private array $items = [];

    public function get(string $key): mixed
    {
        // Возвращает null, если ключ отсутствует.
        return $this->items[$key] ?? null;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        // В реальной реализации нужно учитывать TTL.
        $this->items[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }
}
```
