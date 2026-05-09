<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Cache;

use Andy87\PhpClientSdk\Contracts\CacheInterface;

/**
 * Адаптирует PSR-16/simple-cache совместимый объект к CacheInterface без прямой Composer-зависимости.
 */
class SimpleCacheAdapter implements CacheInterface
{
    /**
     * Создаёт адаптер для объекта с методами get(), set() и delete().
     *
     * @param object $cache PSR-16/simple-cache совместимый объект.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если объект не содержит обязательные cache-методы.
     */
    public function __construct(
        private object $cache,
    ) {
        foreach (['get', 'set', 'delete'] as $method) {
            if (!method_exists($this->cache, $method)) {
                throw new \InvalidArgumentException(sprintf('Simple cache object must have method "%s".', $method));
            }
        }
    }

    /**
     * Возвращает значение из simple-cache хранилища.
     *
     * @param string $key Ключ кеша.
     *
     * @return mixed Значение или null, если ключ отсутствует.
     *
     * @throws \RuntimeException Если чтение из cache-хранилища завершилось ошибкой.
     */
    public function get(string $key): mixed
    {
        try {
            return $this->cache->get($key, null);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Simple cache read failed.', 0, $exception);
        }
    }

    /**
     * Сохраняет значение в simple-cache хранилище.
     *
     * @param string $key Ключ кеша.
     * @param mixed $value Значение.
     * @param int $ttl Время жизни в секундах.
     *
     * @return void
     *
     * @throws \RuntimeException Если запись в cache-хранилище завершилась ошибкой.
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        if ($ttl <= 0) {
            $this->delete($key);

            return;
        }

        try {
            $stored = $this->cache->set($key, $value, $ttl);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Simple cache write failed.', 0, $exception);
        }

        if ($stored !== true) {
            throw new \RuntimeException('Simple cache write failed.');
        }
    }

    /**
     * Удаляет значение из simple-cache хранилища.
     *
     * @param string $key Ключ кеша.
     *
     * @return void
     *
     * @throws \RuntimeException Если удаление из cache-хранилища завершилось ошибкой.
     */
    public function delete(string $key): void
    {
        try {
            $deleted = $this->cache->delete($key);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Simple cache delete failed.', 0, $exception);
        }

        if ($deleted !== true) {
            throw new \RuntimeException('Simple cache delete failed.');
        }
    }
}
