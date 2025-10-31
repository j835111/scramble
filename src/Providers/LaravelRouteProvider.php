<?php

namespace Dedoc\Scramble\Providers;

use Dedoc\Scramble\Adapters\LaravelRouteAdapter;
use Dedoc\Scramble\Contracts\RouteContract;
use Dedoc\Scramble\Contracts\RouteProvider;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Laravel Route Provider
 *
 * Retrieves routes from Laravel's routing system.
 */
class LaravelRouteProvider implements RouteProvider
{
    /**
     * @return array<int, RouteContract>
     */
    public function getRoutes(): array
    {
        return collect(RouteFacade::getRoutes())
            ->map(fn (Route $route) => new LaravelRouteAdapter($route))
            ->values()
            ->all();
    }

    public function getFramework(): string
    {
        return 'laravel';
    }
}
