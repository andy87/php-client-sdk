<?php

declare(strict_types=1);

namespace Andy87\PhpClientSdk\Mock;

use Andy87\PhpClientSdk\Http\HttpRequest;
use Andy87\PhpClientSdk\Http\HttpResponse;

/**
 * Resolver mock-ответов, перебирающий несколько resolver-ов по порядку.
 */
class CompositeMockResponseResolver implements MockResponseResolverInterface
{
    /** @var list<MockResponseResolverInterface> Resolver-ы mock-ответов. */
    private array $resolvers = [];

    /**
     * Создаёт composite resolver.
     *
     * @param list<MockResponseResolverInterface> $resolvers Resolver-ы mock-ответов.
     *
     * @return void
     */
    public function __construct(array $resolvers = [])
    {
        foreach ($resolvers as $resolver) {
            $this->add($resolver);
        }
    }

    /**
     * Добавляет resolver в конец цепочки.
     *
     * @param MockResponseResolverInterface $resolver Resolver mock-ответов.
     *
     * @return static Текущий composite resolver.
     */
    public function add(MockResponseResolverInterface $resolver): static
    {
        $this->resolvers[] = $resolver;

        return $this;
    }

    /**
     * Возвращает первый найденный mock-ответ.
     *
     * @param HttpRequest $request HTTP-запрос.
     *
     * @return HttpResponse|null Mock-ответ или null.
     */
    public function resolve(HttpRequest $request): ?HttpResponse
    {
        foreach ($this->resolvers as $resolver) {
            $response = $resolver->resolve($request);

            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }
}
