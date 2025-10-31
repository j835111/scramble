<?php

namespace Dedoc\Scramble\Reflection;

use Dedoc\Scramble\Contracts\RouteContract;
use Dedoc\Scramble\Support\RouteAdapterFactory;
use Illuminate\Routing\Route as LaravelRoute;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * Factory for creating framework-specific route reflection handlers.
 *
 * @internal
 */
class ReflectionRouteFactory
{
    /**
     * Create a reflection route handler for any supported route type.
     *
     * @param  mixed  $route
     * @return ReflectionRoute|SymfonyReflectionRoute
     */
    public static function create($route)
    {
        // Convert to RouteContract if needed
        if (! $route instanceof RouteContract) {
            if ($route instanceof LaravelRoute) {
                return ReflectionRoute::createFromRoute($route);
            }

            if ($route instanceof SymfonyRoute) {
                $adapter = RouteAdapterFactory::create($route);

                return SymfonyReflectionRoute::createFromRoute($adapter);
            }
        }

        // Handle RouteContract instances
        $framework = $route->getFramework();

        return match ($framework) {
            'laravel' => ReflectionRoute::createFromRoute($route->getOriginalRoute()),
            'symfony' => SymfonyReflectionRoute::createFromRoute($route),
            default => throw new \InvalidArgumentException("Unsupported framework: {$framework}"),
        };
    }

    /**
     * Get signature parameters map for any route.
     *
     * @param  mixed  $route
     */
    public static function getSignatureParametersMap($route): array
    {
        $reflection = static::create($route);

        return $reflection->getSignatureParametersMap();
    }

    /**
     * Get bound parameters types for any route.
     *
     * @param  mixed  $route
     */
    public static function getBoundParametersTypes($route): array
    {
        $reflection = static::create($route);

        return $reflection->getBoundParametersTypes();
    }
}
