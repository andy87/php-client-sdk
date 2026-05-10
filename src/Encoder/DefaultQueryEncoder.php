<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Encoder;

use and_y87\PhpClientSdk\Contracts\QueryEncoderInterface;

/**
 * Кодирует query-параметры стандартным PHP-форматом RFC 3986.
 */
class DefaultQueryEncoder implements QueryEncoderInterface
{
    /**
     * Кодирует query-параметры.
     *
     * @param array<string, mixed> $query Query-параметры.
     *
     * @return string Query-string или пустая строка.
     */
    public function encode(array $query): string
    {
        $query = array_filter($query, static fn (mixed $value): bool => $value !== null && $value !== []);

        if ($query === []) {
            return '';
        }

        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Кодирует query-параметры с учётом OpenAPI style/explode.
     *
     * @param array<string, mixed> $query Query-параметры.
     * @param array<string, array{style?:string,explode?:bool}> $styles Правила OpenAPI по имени query-параметра.
     *
     * @return string Query-string или пустая строка.
     */
    public function encodeWithStyles(array $query, array $styles): string
    {
        if ($styles === []) {
            return $this->encode($query);
        }

        $pairs = [];

        foreach ($query as $name => $value) {
            if ($value === null || $value === []) {
                continue;
            }

            $style = $styles[$name]['style'] ?? 'form';
            $explode = $styles[$name]['explode'] ?? true;

            foreach ($this->buildPairs((string) $name, $value, (string) $style, (bool) $explode) as $pair) {
                $pairs[] = $pair;
            }
        }

        return implode('&', $pairs);
    }

    /**
     * Собирает пары query-string для одного параметра.
     *
     * @param string $name Имя query-параметра.
     * @param mixed $value Значение query-параметра.
     * @param string $style OpenAPI style.
     * @param bool $explode OpenAPI explode.
     *
     * @return list<string> Закодированные пары query-string.
     */
    private function buildPairs(string $name, mixed $value, string $style, bool $explode): array
    {
        if ($style === 'deepObject' && is_array($value)) {
            $pairs = [];

            foreach ($value as $key => $item) {
                if ($item === null || $item === []) {
                    continue;
                }

                $pairs[] = $this->pair(sprintf('%s[%s]', $name, (string) $key), $this->scalar($item));
            }

            return $pairs;
        }

        if (!is_array($value)) {
            return [$this->pair($name, $this->scalar($value))];
        }

        if ($explode) {
            $pairs = [];

            foreach ($value as $key => $item) {
                if ($item === null || $item === []) {
                    continue;
                }

                $pairName = $this->isList($value) ? $name : (string) $key;
                $pairs[] = $this->pair($pairName, $this->scalar($item));
            }

            return $pairs;
        }

        $separator = match ($style) {
            'spaceDelimited' => ' ',
            'pipeDelimited' => '|',
            default => ',',
        };

        $items = [];

        foreach ($value as $key => $item) {
            if (!$this->isList($value)) {
                $items[] = (string) $key;
            }

            $items[] = $this->scalar($item);
        }

        return [$this->pair($name, implode($separator, $items))];
    }

    /**
     * Кодирует одну пару query-string.
     *
     * @param string $name Имя query-параметра.
     * @param string $value Значение query-параметра.
     *
     * @return string Закодированная пара.
     */
    private function pair(string $name, string $value): string
    {
        return rawurlencode($name) . '=' . rawurlencode($value);
    }

    /**
     * Приводит query-значение к строке.
     *
     * @param mixed $value Значение query-параметра.
     *
     * @return string Строковое значение.
     *
     * @throws \InvalidArgumentException Если значение нельзя представить строкой.
     */
    private function scalar(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value) || is_string($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        throw new \InvalidArgumentException('Query value must be scalar, array or Stringable.');
    }

    /**
     * Проверяет, что массив является list.
     *
     * @param array<mixed> $value Массив.
     *
     * @return bool true, если массив является list.
     */
    private function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
