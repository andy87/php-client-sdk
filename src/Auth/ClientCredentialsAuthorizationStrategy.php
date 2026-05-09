<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Auth;

use Andy87\PhpClientSdk\Contracts\CacheInterface;
use Andy87\PhpClientSdk\Contracts\HttpTransportInterface;
use Andy87\PhpClientSdk\Contracts\RefreshableAuthorizationStrategyInterface;
use Andy87\PhpClientSdk\Exception\AuthorizationException;
use Andy87\PhpClientSdk\Http\HttpRequest;

/**
 * Выполняет OAuth client_credentials авторизацию и кеширует access token.
 */
class ClientCredentialsAuthorizationStrategy implements RefreshableAuthorizationStrategyInterface
{
    private ?string $accessToken = null;
    private int $expiresAt = 0;
    private string $resolvedTokenCacheKey;

    /**
     * Создаёт стратегию OAuth client_credentials.
     *
     * @param string $tokenUrl URL получения токена.
     * @param string $clientId Client ID.
     * @param string $clientSecret Client Secret.
     * @param string|null $scope OAuth scope.
     * @param int $timeout Таймаут запроса токена.
     * @param CacheInterface|null $tokenCache TTL-хранилище OAuth token или null для памяти процесса.
     * @param string|null $tokenCacheKey Ключ хранения токена или null для автоматического ключа.
     * @param int $clockSkew Количество секунд раннего обновления токена до expires_at.
     *
     * @return void
     */
    public function __construct(
        private string $tokenUrl,
        private string $clientId,
        private string $clientSecret,
        private ?string $scope = null,
        private int $timeout = 30,
        private ?CacheInterface $tokenCache = null,
        ?string $tokenCacheKey = null,
        private int $clockSkew = 60,
    ) {
        $this->resolvedTokenCacheKey = $tokenCacheKey ?? $this->createDefaultTokenCacheKey();
    }

    /**
     * Возвращает Bearer-заголовок авторизации.
     *
     * @param HttpTransportInterface $transport Транспорт для запроса токена.
     *
     * @return array<string, string>
     *
     * @throws AuthorizationException Если токен не получен.
     */
    public function getAuthorizationHeaders(HttpTransportInterface $transport): array
    {
        $accessToken = $this->getCachedAccessToken();

        if ($accessToken === null) {
            $accessToken = $this->requestToken($transport);
        }

        return ['Authorization' => 'Bearer ' . $accessToken];
    }

    /**
     * Сбрасывает cached token и запрашивает новый access token.
     *
     * @param HttpTransportInterface $transport Транспорт.
     *
     * @return void
     *
     * @throws AuthorizationException Если API авторизации вернул ошибку.
     */
    public function refreshAuthorization(HttpTransportInterface $transport): void
    {
        $this->clearCachedToken();
        $this->requestToken($transport);
    }

    /**
     * Возвращает access token из настроенного кеша или памяти процесса.
     *
     * @return string|null Access token или null, если токен отсутствует либо истекает слишком скоро.
     *
     * @throws AuthorizationException Если сохранённые данные токена имеют некорректный формат.
     */
    private function getCachedAccessToken(): ?string
    {
        if ($this->tokenCache === null) {
            return $this->isTokenFresh($this->expiresAt) ? $this->accessToken : null;
        }

        $cachedToken = $this->tokenCache->get($this->resolvedTokenCacheKey);

        if ($cachedToken === null) {
            return null;
        }

        if (!is_array($cachedToken)) {
            $this->tokenCache->delete($this->resolvedTokenCacheKey);

            throw new AuthorizationException('Cached OAuth token payload must be an array.');
        }

        $accessToken = $cachedToken['access_token'] ?? null;
        $expiresAt = $cachedToken['expires_at'] ?? null;

        if (!is_string($accessToken) || trim($accessToken) === '' || !is_int($expiresAt)) {
            $this->tokenCache->delete($this->resolvedTokenCacheKey);

            throw new AuthorizationException('Cached OAuth token payload is invalid.');
        }

        if (!$this->isTokenFresh($expiresAt)) {
            $this->tokenCache->delete($this->resolvedTokenCacheKey);

            return null;
        }

        return $accessToken;
    }

    /**
     * Проверяет, можно ли ещё использовать токен с учётом раннего обновления.
     *
     * @param int $expiresAt Unix timestamp истечения токена.
     *
     * @return bool true, если токен ещё пригоден для запроса.
     */
    private function isTokenFresh(int $expiresAt): bool
    {
        return $expiresAt > time() + max(0, $this->clockSkew);
    }

    /**
     * Удаляет токен из настроенного кеша или памяти процесса.
     *
     * @return void
     */
    private function clearCachedToken(): void
    {
        $this->accessToken = null;
        $this->expiresAt = 0;
        $this->tokenCache?->delete($this->resolvedTokenCacheKey);
    }

    /**
     * Запрашивает новый access token.
     *
     * @param HttpTransportInterface $transport Транспорт.
     *
     * @return string Новый access token.
     *
     * @throws AuthorizationException Если API авторизации вернул ошибку.
     */
    private function requestToken(HttpTransportInterface $transport): string
    {
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];

        if ($this->scope !== null) {
            $body['scope'] = $this->scope;
        }

        $rawBody = http_build_query($body);

        try {
            $response = $transport->send(new HttpRequest(
                method: 'POST',
                url: $this->tokenUrl,
                headers: [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                body: $body,
                contentType: 'application/x-www-form-urlencoded',
                timeout: $this->timeout,
                rawBody: $rawBody,
            ));

            $data = $response->json();
        } catch (\Throwable $exception) {
            throw new AuthorizationException('OAuth client_credentials authorization failed.', 0, $exception);
        }

        $accessToken = $data['access_token'] ?? null;

        if ($response->statusCode >= 400 || !is_string($accessToken) || trim($accessToken) === '') {
            throw new AuthorizationException('OAuth client_credentials authorization failed.');
        }

        $this->accessToken = $accessToken;
        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        $this->expiresAt = time() + max(1, $expiresIn);

        if ($this->tokenCache !== null) {
            $this->tokenCache->set($this->resolvedTokenCacheKey, [
                'access_token' => $this->accessToken,
                'expires_at' => $this->expiresAt,
            ], max(1, $this->expiresAt - time()));
        }

        return $this->accessToken;
    }

    /**
     * Создаёт детерминированный ключ кеша для client_credentials токена.
     *
     * @return string Ключ кеша.
     */
    private function createDefaultTokenCacheKey(): string
    {
        return 'oauth_client_credentials:' . sha1(implode('|', [
            $this->tokenUrl,
            $this->clientId,
            $this->scope ?? '',
        ]));
    }
}
