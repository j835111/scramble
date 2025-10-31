<?php

namespace Dedoc\Scramble\Tests\Support;

use Dedoc\Scramble\Adapters\LaravelRouteAdapter;
use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Support\RouteAdapterFactory;
use Illuminate\Support\Facades\Route as RouteFacade;
use InvalidArgumentException;
use Symfony\Component\Routing\Route as SymfonyRoute;

test('creates Laravel adapter from Laravel route', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = RouteAdapterFactory::create($route);

    expect($adapter)->toBeInstanceOf(LaravelRouteAdapter::class);
    expect($adapter->getFramework())->toBe('laravel');
});

test('creates Symfony adapter from Symfony route', function () {
    $route = new SymfonyRoute('/test');
    $adapter = RouteAdapterFactory::create($route);

    expect($adapter)->toBeInstanceOf(SymfonyRouteAdapter::class);
    expect($adapter->getFramework())->toBe('symfony');
});

test('returns RouteContract as-is', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    $result = RouteAdapterFactory::create($adapter);

    expect($result)->toBe($adapter);
});

test('passes route name option to Symfony adapter', function () {
    $route = new SymfonyRoute('/test');
    $adapter = RouteAdapterFactory::create($route, ['name' => 'test.route']);

    expect($adapter)->toBeInstanceOf(SymfonyRouteAdapter::class);
    expect($adapter->getName())->toBe('test.route');
});

test('detects Laravel framework', function () {
    $route = RouteFacade::get('/test', fn () => 'test');

    expect(RouteAdapterFactory::detectFramework($route))->toBe('laravel');
});

test('detects Symfony framework', function () {
    $route = new SymfonyRoute('/test');

    expect(RouteAdapterFactory::detectFramework($route))->toBe('symfony');
});

test('detects framework from RouteContract', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect(RouteAdapterFactory::detectFramework($adapter))->toBe('laravel');
});

test('returns null for unsupported route types', function () {
    $route = new \stdClass;

    expect(RouteAdapterFactory::detectFramework($route))->toBeNull();
});

test('checks if Laravel route is supported', function () {
    $route = RouteFacade::get('/test', fn () => 'test');

    expect(RouteAdapterFactory::isSupported($route))->toBeTrue();
});

test('checks if Symfony route is supported', function () {
    $route = new SymfonyRoute('/test');

    expect(RouteAdapterFactory::isSupported($route))->toBeTrue();
});

test('checks if RouteContract is supported', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect(RouteAdapterFactory::isSupported($adapter))->toBeTrue();
});

test('checks if unsupported route type is not supported', function () {
    $route = new \stdClass;

    expect(RouteAdapterFactory::isSupported($route))->toBeFalse();
});

test('throws exception for unsupported route type', function () {
    $route = new \stdClass;

    RouteAdapterFactory::create($route);
})->throws(InvalidArgumentException::class, 'Unsupported route type');

test('exception message includes route type', function () {
    $route = new \stdClass;

    try {
        RouteAdapterFactory::create($route);
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('stdClass');
        expect($e->getMessage())->toContain('Illuminate\Routing\Route');
        expect($e->getMessage())->toContain('Symfony\Component\Routing\Route');
    }
});
