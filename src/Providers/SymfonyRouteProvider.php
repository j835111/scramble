<?php

namespace Dedoc\Scramble\Providers;

use Dedoc\Scramble\Contracts\RouteContract;
use Dedoc\Scramble\Contracts\RouteProvider;
use Dedoc\Scramble\Support\SymfonyRouteCollector;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * Symfony Route Provider
 *
 * Retrieves routes from Symfony's routing system.
 */
class SymfonyRouteProvider implements RouteProvider
{
    private ?SymfonyRouteCollector $collector = null;

    public function __construct(
        private ?Router $router = null,
        private ?RouteCollection $routeCollection = null
    ) {
        $this->collector = new SymfonyRouteCollector($this->router);
    }

    /**
     * @return array<int, RouteContract>
     */
    public function getRoutes(): array
    {
        return $this->collector->getRoutes($this->routeCollection);
    }

    public function getFramework(): string
    {
        return 'symfony';
    }

    /**
     * Create from a Symfony router instance.
     */
    public static function fromRouter(Router $router): self
    {
        return new self($router);
    }

    /**
     * Create from a route collection.
     */
    public static function fromRouteCollection(RouteCollection $routeCollection): self
    {
        return new self(null, $routeCollection);
    }
}
