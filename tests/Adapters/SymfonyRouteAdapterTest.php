<?php

namespace Dedoc\Scramble\Tests\Adapters;

use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Symfony\Component\Routing\Route;

// Don't use TestCase to avoid database dependencies
uses()->in(__DIR__);

test('can create adapter from Symfony route', function () {
    $route = new Route('/test');
    $adapter = SymfonyRouteAdapter::fromRoute($route, 'test_route');

    expect($adapter)->toBeInstanceOf(SymfonyRouteAdapter::class);
    expect($adapter->getFramework())->toBe('symfony');
});

test('returns correct HTTP methods', function () {
    $route = new Route('/test', methods: ['POST', 'PUT']);
    $adapter = new SymfonyRouteAdapter($route);

    $methods = $adapter->getMethods();
    expect($methods)->toContain('POST');
    expect($methods)->toContain('PUT');
});

test('returns all methods when none specified', function () {
    $route = new Route('/test');
    $adapter = new SymfonyRouteAdapter($route);

    $methods = $adapter->getMethods();
    expect($methods)->toContain('GET');
    expect($methods)->toContain('POST');
    expect($methods)->toContain('PUT');
    expect($methods)->toContain('DELETE');
});

test('returns correct path', function () {
    $route = new Route('/users/{id}');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getUri())->toBe('/users/{id}');
});

test('identifies class-based routes correctly', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', 'App\Controller\TestController::index');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->isClassBased())->toBeTrue();
});

test('identifies closure routes correctly', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', fn () => 'test');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->isClassBased())->toBeFalse();
});

test('extracts controller class from double colon format', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', 'App\Controller\UserController::show');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getControllerClass())->toBe('App\Controller\UserController');
});

test('extracts controller method from double colon format', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', 'App\Controller\UserController::show');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getControllerMethod())->toBe('show');
});

test('converts controller format from double colon to at sign', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', 'App\Controller\UserController::show');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getAction())->toBe('App\Controller\UserController@show');
});

test('handles invokable controllers', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', 'App\Controller\InvokableController');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getControllerClass())->toBe('App\Controller\InvokableController');
    expect($adapter->getControllerMethod())->toBe('__invoke');
});

test('extracts parameter names from path', function () {
    $route = new Route('/users/{userId}/posts/{postId}');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getParameterNames())->toBe(['userId', 'postId']);
});

test('handles optional parameters in path', function () {
    $route = new Route('/users/{id}/{slug?}');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getParameterNames())->toContain('id');
    expect($adapter->getParameterNames())->toContain('slug');
});

test('returns route name', function () {
    $route = new Route('/test');
    $adapter = new SymfonyRouteAdapter($route, 'test.route');

    expect($adapter->getName())->toBe('test.route');
});

test('returns host domain', function () {
    $route = new Route('/test', host: 'api.example.com');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getDomain())->toBe('api.example.com');
});

test('returns original route object', function () {
    $route = new Route('/test');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getOriginalRoute())->toBe($route);
});

test('handles array controller format', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', ['App\Controller\TestController', 'index']);
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getControllerClass())->toBe('App\Controller\TestController');
    expect($adapter->getControllerMethod())->toBe('index');
    expect($adapter->getAction())->toBe('App\Controller\TestController@index');
});

test('returns null for controller class on closure routes', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', fn () => 'test');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getControllerClass())->toBeNull();
    expect($adapter->getControllerMethod())->toBeNull();
});

test('handles routes without controller', function () {
    $route = new Route('/test');
    $adapter = new SymfonyRouteAdapter($route);

    expect($adapter->getAction())->toBeNull();
    expect($adapter->isClassBased())->toBeFalse();
});
