<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Security\Authorization\Strategy;

use and_y87\PhpClientSdk\Contracts\Auth\AuthorizationStrategyInterface;
use and_y87\PhpClientSdk\Contracts\Http\HttpTransportInterface;

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
