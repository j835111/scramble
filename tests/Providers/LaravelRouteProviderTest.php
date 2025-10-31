<?php

namespace Dedoc\Scramble\Tests\Providers;

use Dedoc\Scramble\Adapters\LaravelRouteAdapter;
use Dedoc\Scramble\Providers\LaravelRouteProvider;
use Illuminate\Support\Facades\Route as RouteFacade;

test('returns framework identifier', function () {
    $provider = new LaravelRouteProvider;

    expect($provider->getFramework())->toBe('laravel');
});

test('gets routes from Laravel router', function () {
    RouteFacade::get('/test1', fn () => 'test1');
    RouteFacade::post('/test2', fn () => 'test2');

    $provider = new LaravelRouteProvider;
    $routes = $provider->getRoutes();

    expect($routes)->toBeArray();
    expect(count($routes))->toBeGreaterThan(0);
});

test('returns RouteContract instances', function () {
    RouteFacade::get('/test', fn () => 'test');

    $provider = new LaravelRouteProvider;
    $routes = $provider->getRoutes();

    foreach ($routes as $route) {
        expect($route)->toBeInstanceOf(LaravelRouteAdapter::class);
    }
});

test('routes include URI information', function () {
    RouteFacade::get('/my-test-route', fn () => 'test');

    $provider = new LaravelRouteProvider;
    $routes = $provider->getRoutes();

    $testRoute = collect($routes)->first(fn ($r) => str_contains($r->getUri(), 'my-test-route'));

    expect($testRoute)->not->toBeNull();
    expect($testRoute->getUri())->toContain('my-test-route');
});
