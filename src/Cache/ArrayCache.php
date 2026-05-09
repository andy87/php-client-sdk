<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Cache;

use Andy87\PhpClientSdk\Contracts\CacheInterface;

/**
 * Хранит значения в памяти текущего PHP-процесса с проверкой TTL.
 */
class ArrayCache implements CacheInterface
{
    /**
     * @var array<string, array{value: mixed, expiresAt: int}>
     */
    private array $items = [];

    /**
     * Возвращает значение из памяти процесса.
     *
     * @param string $key Ключ кеша.
     *
     * @return mixed Значение или null, если ключ отсутствует или истек.
     */
    public function get(string $key): mixed
    {
        $item = $this->items[$key] ?? null;

        if ($item === null) {
            return null;
        }

        if ($item['expiresAt'] <= time()) {
            unset($this->items[$key]);

            return null;
        }

        return $item['value'];
    }

    /**
     * Сохраняет значение в памяти процесса.
     *
     * @param string $key Ключ кеша.
     * @param mixed $value Значение.
     * @param int $ttl Время жизни в секундах.
     *
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        if ($ttl <= 0) {
            unset($this->items[$key]);

            return;
        }

        $this->items[$key] = [
            'value' => $value,
            'expiresAt' => time() + $ttl,
        ];
    }

    /**
     * Удаляет значение из памяти процесса.
     *
     * @param string $key Ключ кеша.
     *
     * @return void
     */
    public function delete(string $key): void
    {
        unset($this->items[$key]);
    }
}
