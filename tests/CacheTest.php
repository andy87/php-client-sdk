<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Tests;

use Andy87\PhpClientSdk\Cache\ArrayCache;
use Andy87\PhpClientSdk\Cache\SimpleCacheAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет TTL-хранилища SDK и адаптеры к внешним cache-компонентам.
 */
class CacheTest extends TestCase
{
    /**
     * Проверяет запись, чтение и удаление значения из memory cache.
     *
     * @return void
     */
    public function testArrayCacheStoresReadsAndDeletesValue(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', ['value' => 'test'], 60);

        self::assertSame(['value' => 'test'], $cache->get('key'));

        $cache->delete('key');

        self::assertNull($cache->get('key'));
    }

    /**
     * Проверяет, что memory cache не возвращает значение с неположительным TTL.
     *
     * @return void
     */
    public function testArrayCacheDropsValueWithNonPositiveTtl(): void
    {
        $cache = new ArrayCache();

        $cache->set('key', 'value', 0);

        self::assertNull($cache->get('key'));
    }

    /**
     * Проверяет, что SimpleCacheAdapter проксирует get(), set() и delete().
     *
     * @return void
     */
    public function testSimpleCacheAdapterProxiesCompatibleObject(): void
    {
        $simpleCache = new class {
            /** @var array<string, mixed> */
            public array $items = [];

            /** @var array<string, int> */
            public array $ttl = [];

            /**
             * Возвращает значение из fake simple cache.
             *
             * @param string $key Ключ кеша.
             * @param mixed $default Значение по умолчанию.
             *
             * @return mixed Значение или default, если ключ отсутствует.
             */
            public function get(string $key, mixed $default = null): mixed
            {
                return $this->items[$key] ?? $default;
            }

            /**
             * Сохраняет значение в fake simple cache.
             *
             * @param string $key Ключ кеша.
             * @param mixed $value Значение.
             * @param int|null $ttl Время жизни в секундах.
             *
             * @return bool true при успешной записи.
             */
            public function set(string $key, mixed $value, ?int $ttl = null): bool
            {
                $this->items[$key] = $value;
                $this->ttl[$key] = $ttl ?? 0;

                return true;
            }

            /**
             * Удаляет значение из fake simple cache.
             *
             * @param string $key Ключ кеша.
             *
             * @return bool true при успешном удалении.
             */
            public function delete(string $key): bool
            {
                unset($this->items[$key], $this->ttl[$key]);

                return true;
            }
        };
        $adapter = new SimpleCacheAdapter($simpleCache);

        self::assertNull($adapter->get('missing'));

        $adapter->set('key', ['value' => 'test'], 120);

        self::assertSame(['value' => 'test'], $adapter->get('key'));
        self::assertSame(120, $simpleCache->ttl['key']);

        $adapter->delete('key');

        self::assertNull($adapter->get('key'));
    }

    /**
     * Проверяет защиту SimpleCacheAdapter от несовместимого объекта.
     *
     * @return void
     */
    public function testSimpleCacheAdapterRejectsInvalidObject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SimpleCacheAdapter(new \stdClass());
    }
}
