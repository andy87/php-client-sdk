<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Contracts;

/**
 * Описывает TTL-хранилище значений для SDK.
 */
interface CacheInterface
{
    /**
     * Возвращает значение из кеша.
     *
     * @param string $key Ключ кеша.
     *
     * @return mixed Значение или null, если ключ отсутствует.
     *
     * @throws \RuntimeException Если чтение из кеша завершилось ошибкой.
     */
    public function get(string $key): mixed;

    /**
     * Сохраняет значение в кеш.
     *
     * @param string $key Ключ кеша.
     * @param mixed $value Значение.
     * @param int $ttl Время жизни в секундах.
     *
     * @return void
     *
     * @throws \RuntimeException Если запись в кеш завершилась ошибкой.
     */
    public function set(string $key, mixed $value, int $ttl): void;

    /**
     * Удаляет значение из кеша.
     *
     * @param string $key Ключ кеша.
     *
     * @return void
     *
     * @throws \RuntimeException Если удаление из кеша завершилось ошибкой.
     */
    public function delete(string $key): void;
}
