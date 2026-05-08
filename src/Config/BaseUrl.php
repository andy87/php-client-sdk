<?php

declare(strict_types=1);

namespace Andy87\ClientsBase\Config;

/**
 * Хранит составные части базового URL API.
 */
class BaseUrl implements \Stringable
{
    /**
     * Создаёт базовый URL API из частей.
     *
     * @param string $host Хост API.
     * @param string $protocol HTTP-протокол.
     * @param string|null $prefix Префикс пути API.
     * @param int|null $port Порт API.
     *
     * @return void
     *
     * @throws \InvalidArgumentException Если protocol, host или port некорректны.
     */
    public function __construct(
        private string $host,
        private string $protocol = 'https',
        private ?string $prefix = null,
        private ?int $port = null,
    ) {
        $this->host = trim($host);
        $this->protocol = strtolower(trim($protocol));
        $this->prefix = $prefix !== null ? trim($prefix, " \t\n\r\0\x0B/") : null;

        if ($this->host === '') {
            throw new \InvalidArgumentException('Base URL host must be a non-empty string.');
        }

        if (!in_array($this->protocol, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Base URL protocol must be "http" or "https".');
        }

        if ($this->port !== null && ($this->port < 1 || $this->port > 65535)) {
            throw new \InvalidArgumentException('Base URL port must be between 1 and 65535.');
        }
    }

    /**
     * Возвращает базовый URL строкой.
     *
     * @return string Базовый URL.
     */
    public function __toString(): string
    {
        $url = $this->protocol . '://' . $this->host;

        if ($this->port !== null) {
            $url .= ':' . $this->port;
        }

        if ($this->prefix !== null && $this->prefix !== '') {
            $url .= '/' . $this->prefix;
        }

        return $url;
    }
}
