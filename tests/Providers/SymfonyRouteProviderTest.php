<?php

namespace Dedoc\Scramble\Tests\Providers;

use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Providers\SymfonyRouteProvider;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

test('returns framework identifier', function () {
    $collection = new RouteCollection;
    $provider = SymfonyRouteProvider::fromRouteCollection($collection);

    expect($provider->getFramework())->toBe('symfony');
});

test('gets routes from route collection', function () {
    $collection = new RouteCollection;
    $collection->add('route1', new Route('/test1'));
    $collection->add('route2', new Route('/test2'));

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    expect($routes)->toBeArray();
    expect(count($routes))->toBe(2);
});

test('returns RouteContract instances', function () {
    $collection = new RouteCollection;
    $collection->add('test_route', new Route('/test'));

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    foreach ($routes as $route) {
        expect($route)->toBeInstanceOf(SymfonyRouteAdapter::class);
    }
});

test('skips internal Symfony routes', function () {
    $collection = new RouteCollection;
    $collection->add('test_route', new Route('/test'));
    $collection->add('_profiler_home', new Route('/_profiler'));
    $collection->add('_wdt', new Route('/_wdt'));

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    expect(count($routes))->toBe(1);
    expect($routes[0]->getName())->toBe('test_route');
});

test('preserves route names', function () {
    $collection = new RouteCollection;
    $collection->add('user.show', new Route('/users/{id}'));

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    expect($routes[0]->getName())->toBe('user.show');
});

test('handles empty route collection', function () {
    $collection = new RouteCollection;

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    expect($routes)->toBeArray();
    expect(count($routes))->toBe(0);
});
