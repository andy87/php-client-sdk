<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Auth;

use and_y87\PhpClientSdk\Contracts\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\HttpTransportInterface;

/**
 * Отключает авторизацию для публичных API-методов.
 */
class NullAuthorizationStrategy implements AuthorizationStrategyInterface
{
    /**
     * Возвращает пустой набор заголовков авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт.
     *
     * @return array<string, string>
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        return [];
    }
}
