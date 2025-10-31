<?php

declare(strict_types=1);

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\RouteProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteProviderTest extends TestCase
{
    public function testGetRoutesFromRouteCollection(): void
    {
        $collection = new RouteCollection();
        $collection->add('route1', new Route('/test1'));
        $collection->add('route2', new Route('/test2'));

        $provider = RouteProvider::fromRouteCollection($collection);
        $routes = $provider->getRoutes($collection);

        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('route1', $routes);
        $this->assertArrayHasKey('route2', $routes);
    }

    public function testSkipsInternalSymfonyRoutes(): void
    {
        $collection = new RouteCollection();
        $collection->add('test_route', new Route('/test'));
        $collection->add('_profiler_home', new Route('/_profiler'));
        $collection->add('_wdt', new Route('/_wdt'));
        $collection->add('_preview_error', new Route('/_error'));

        $provider = RouteProvider::fromRouteCollection($collection);
        $routes = $provider->getRoutes($collection);

        $this->assertCount(1, $routes);
        $this->assertArrayHasKey('test_route', $routes);
        $this->assertArrayNotHasKey('_profiler_home', $routes);
        $this->assertArrayNotHasKey('_wdt', $routes);
        $this->assertArrayNotHasKey('_preview_error', $routes);
    }

    public function testHandlesEmptyRouteCollection(): void
    {
        $collection = new RouteCollection();

        $provider = RouteProvider::fromRouteCollection($collection);
        $routes = $provider->getRoutes($collection);

        $this->assertIsArray($routes);
        $this->assertEmpty($routes);
    }

    public function testPreservesRouteNames(): void
    {
        $collection = new RouteCollection();
        $collection->add('user.show', new Route('/users/{id}'));

        $provider = RouteProvider::fromRouteCollection($collection);
        $routes = $provider->getRoutes($collection);

        $this->assertArrayHasKey('user.show', $routes);
    }
}
