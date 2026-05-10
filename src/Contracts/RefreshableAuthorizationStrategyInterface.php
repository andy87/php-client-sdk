<?php

declare(strict_types=1);

namespace and_y87\PhpClientSdk\Contracts;

/**
 * Описывает стратегию авторизации, которая может принудительно обновить credentials.
 */
interface RefreshableAuthorizationStrategyInterface extends AuthorizationStrategyInterface
{
    /**
     * Принудительно обновляет данные авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для служебных запросов авторизации.
     *
     * @return void
     *
     * @throws \RuntimeException Если обновление авторизации не выполнено.
     */
    public function refreshAuthorization(HttpTransportInterface $transport): void;
}
