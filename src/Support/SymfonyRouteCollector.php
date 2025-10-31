<?php

namespace Dedoc\Scramble\Support;

use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Contracts\RouteContract;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * Symfony Route Collector
 *
 * Collects and converts Symfony routes to RouteContract instances.
 */
class SymfonyRouteCollector
{
    public function __construct(
        private ?Router $router = null
    ) {}

    /**
     * Get all routes from Symfony router.
     *
     * @return array<int, RouteContract>
     */
    public function getRoutes(?RouteCollection $routeCollection = null): array
    {
        $collection = $routeCollection ?? $this->router?->getRouteCollection();

        if (! $collection) {
            return [];
        }

        $routes = [];

        foreach ($collection->all() as $routeName => $route) {
            // Skip internal Symfony routes
            if ($this->isInternalRoute($routeName)) {
                continue;
            }

            $routes[] = new SymfonyRouteAdapter($route, $routeName);
        }

        return $routes;
    }

    /**
     * Check if a route is an internal Symfony route that should be skipped.
     */
    private function isInternalRoute(string $routeName): bool
    {
        // Skip profiler routes
        if (str_starts_with($routeName, '_wdt')) {
            return true;
        }

        if (str_starts_with($routeName, '_profiler')) {
            return true;
        }

        if (str_starts_with($routeName, '_preview_error')) {
            return true;
        }

        // Skip other framework routes
        if (str_starts_with($routeName, '_')) {
            return true;
        }

        return false;
    }

    /**
     * Create a collector instance from a router.
     */
    public static function fromRouter(Router $router): self
    {
        return new self($router);
    }

    /**
     * Create a collector instance from a route collection.
     */
    public static function fromRouteCollection(RouteCollection $routeCollection): self
    {
        $collector = new self;

        return $collector;
    }
}
