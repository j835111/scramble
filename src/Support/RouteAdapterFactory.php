<?php

namespace Dedoc\Scramble\Support;

use Dedoc\Scramble\Adapters\LaravelRouteAdapter;
use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Contracts\RouteContract;
use Illuminate\Routing\Route as LaravelRoute;
use InvalidArgumentException;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Route Adapter Factory
 *
 * Automatically detects the framework and creates the appropriate route adapter.
 */
class RouteAdapterFactory
{
    /**
     * Create a route adapter from any supported route object.
     *
     * @param  mixed  $route
     * @param  array<string, mixed>  $options
     *
     * @throws InvalidArgumentException
     */
    public static function create($route, array $options = []): RouteContract
    {
        // If already a RouteContract, return as-is
        if ($route instanceof RouteContract) {
            return $route;
        }

        // Laravel Route
        if ($route instanceof LaravelRoute) {
            return new LaravelRouteAdapter($route);
        }

        // Symfony Route
        if ($route instanceof SymfonyRoute) {
            $routeName = $options['name'] ?? null;

            return new SymfonyRouteAdapter($route, $routeName);
        }

        $routeClass = is_object($route) ? get_class($route) : gettype($route);

        throw new InvalidArgumentException(
            "Unsupported route type: {$routeClass}. ".
            'Supported types are: Illuminate\Routing\Route, Symfony\Component\Routing\Route'
        );
    }

    /**
     * Detect the framework from a route object.
     *
     * @param  mixed  $route
     */
    public static function detectFramework($route): ?string
    {
        if ($route instanceof LaravelRoute) {
            return 'laravel';
        }

        if ($route instanceof SymfonyRoute) {
            return 'symfony';
        }

        if ($route instanceof RouteContract) {
            return $route->getFramework();
        }

        return null;
    }

    /**
     * Check if a route object is supported.
     *
     * @param  mixed  $route
     */
    public static function isSupported($route): bool
    {
        return $route instanceof LaravelRoute
            || $route instanceof SymfonyRoute
            || $route instanceof RouteContract;
    }
}
