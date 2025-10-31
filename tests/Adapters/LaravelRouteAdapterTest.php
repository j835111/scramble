<?php

namespace Dedoc\Scramble\Tests\Adapters;

use Dedoc\Scramble\Adapters\LaravelRouteAdapter;
use Dedoc\Scramble\Tests\TestCase;
use Illuminate\Support\Facades\Route as RouteFacade;

uses(TestCase::class);

test('can create adapter from Laravel route', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = LaravelRouteAdapter::fromRoute($route);

    expect($adapter)->toBeInstanceOf(LaravelRouteAdapter::class);
    expect($adapter->getFramework())->toBe('laravel');
});

test('returns correct HTTP methods', function () {
    $route = RouteFacade::post('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getMethods())->toContain('POST');
});

test('returns correct URI', function () {
    $route = RouteFacade::get('/users/{id}', fn ($id) => $id);
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getUri())->toBe('users/{id}');
});

test('identifies class-based routes correctly', function () {
    $route = RouteFacade::get('/test', 'TestController@index');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->isClassBased())->toBeTrue();
});

test('identifies closure routes correctly', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->isClassBased())->toBeFalse();
});

test('extracts controller class name', function () {
    $route = RouteFacade::get('/test', 'App\Controllers\TestController@index');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getControllerClass())->toBe('App\Controllers\TestController');
});

test('extracts controller method name', function () {
    $route = RouteFacade::get('/test', 'TestController@show');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getControllerMethod())->toBe('show');
});

test('returns route parameter names', function () {
    $route = RouteFacade::get('/users/{userId}/posts/{postId}', fn ($userId, $postId) => null);
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getParameterNames())->toBe(['userId', 'postId']);
});

test('returns route name', function () {
    $route = RouteFacade::get('/test', fn () => 'test')->name('test.route');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getName())->toBe('test.route');
});

test('returns null domain by default', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getDomain())->toBeNull();
});

test('returns original route object', function () {
    $route = RouteFacade::get('/test', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getOriginalRoute())->toBe($route);
});

test('handles routes with leading slash removal', function () {
    $route = RouteFacade::get('/api/users', fn () => 'test');
    $adapter = new LaravelRouteAdapter($route);

    expect($adapter->getUri())->toBe('api/users');
});
