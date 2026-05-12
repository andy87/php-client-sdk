# Transport Cache

Папка содержит реализации кеша, совместимые с `CacheInterface`.

## ArrayCache

`ArrayCache` хранит значения в памяти текущего PHP-процесса и удаляет их после истечения TTL. Это простой вариант для тестов, CLI-скриптов и короткоживущих процессов.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Cache\ArrayCache;

$cache = new ArrayCache();

$cache->set('token', 'abc', ttl: 3600);

echo $cache->get('token'); // abc

$cache->delete('token');
```

## SimpleCacheAdapter

`SimpleCacheAdapter` адаптирует объект с методами `get()`, `set()` и `delete()` к `CacheInterface`. Прямая Composer-зависимость от PSR-16 при этом не нужна.

```php
<?php

declare(strict_types=1);

use and_y87\PhpClientSdk\Transport\Cache\SimpleCacheAdapter;

$psr16LikeCache = new class {
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->items[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }
};

$cache = new SimpleCacheAdapter($psr16LikeCache);
$cache->set('token', 'abc', ttl: 3600);
```
