<?php

namespace Dedoc\Scramble\Tests\Support;

use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Support\RouteInfo;
use Symfony\Component\Routing\Route;

// Test controller for RouteInfo tests
class TestController_RouteInfoSymfonyTest
{
    /**
     * Show user details
     *
     * @param  int  $id  User ID
     */
    public function show(int $id): array
    {
        return ['id' => $id];
    }

    public function list(): array
    {
        return [['id' => 1], ['id' => 2]];
    }
}

test('RouteInfo works with Symfony route adapter', function () {
    $route = new Route('/users/{id}');
    $route->setDefault('_controller', TestController_RouteInfoSymfonyTest::class.'::show');

    $adapter = new SymfonyRouteAdapter($route, 'users.show');
    $infer = app(Infer::class);

    $routeInfo = RouteInfo::fromRouteContract($adapter, $infer);

    expect($routeInfo->isClassBased())->toBeTrue();
    expect($routeInfo->className())->toBe(TestController_RouteInfoSymfonyTest::class);
    expect($routeInfo->methodName())->toBe('show');
});

test('RouteInfo extracts correct reflection method for Symfony', function () {
    $route = new Route('/users/{id}');
    $route->setDefault('_controller', TestController_RouteInfoSymfonyTest::class.'::show');

    $adapter = new SymfonyRouteAdapter($route);
    $infer = app(Infer::class);

    $routeInfo = RouteInfo::fromRouteContract($adapter, $infer);
    $reflection = $routeInfo->reflectionMethod();

    expect($reflection)->not->toBeNull();
    expect($reflection->getName())->toBe('show');
    expect($reflection->getNumberOfParameters())->toBe(1);
});

test('RouteInfo works with closure Symfony routes', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', fn () => 'test');

    $adapter = new SymfonyRouteAdapter($route);
    $infer = app(Infer::class);

    $routeInfo = RouteInfo::fromRouteContract($adapter, $infer);

    expect($routeInfo->isClassBased())->toBeFalse();
    expect($routeInfo->className())->toBeNull();
    expect($routeInfo->methodName())->toBeNull();
});

test('RouteInfo can access PHPDoc from Symfony controller', function () {
    $route = new Route('/users/{id}');
    $route->setDefault('_controller', TestController_RouteInfoSymfonyTest::class.'::show');

    $adapter = new SymfonyRouteAdapter($route);
    $infer = app(Infer::class);

    $routeInfo = RouteInfo::fromRouteContract($adapter, $infer);
    $phpDoc = $routeInfo->phpDoc();

    expect($phpDoc)->not->toBeNull();
});

test('RouteInfo handles Symfony routes without parameters', function () {
    $route = new Route('/users');
    $route->setDefault('_controller', TestController_RouteInfoSymfonyTest::class.'::list');

    $adapter = new SymfonyRouteAdapter($route);
    $infer = app(Infer::class);

    $routeInfo = RouteInfo::fromRouteContract($adapter, $infer);

    expect($routeInfo->isClassBased())->toBeTrue();
    expect($routeInfo->className())->toBe(TestController_RouteInfoSymfonyTest::class);
    expect($routeInfo->methodName())->toBe('list');
});
