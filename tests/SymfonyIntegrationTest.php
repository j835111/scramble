<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Contracts\RouteContract;
use Dedoc\Scramble\Providers\SymfonyRouteProvider;
use Dedoc\Scramble\Support\RouteAdapterFactory;
use Dedoc\Scramble\Support\RouteInfo;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

// Test controller for integration tests
class SymfonyUserController_IntegrationTest
{
    public function list(): array
    {
        return [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ];
    }

    public function show(int $id): array
    {
        return ['id' => $id, 'name' => 'John'];
    }

    public function create(array $data): array
    {
        return ['id' => 3, 'name' => $data['name'] ?? 'Unknown'];
    }
}

test('can create route collection for Symfony', function () {
    $collection = new RouteCollection;
    $collection->add('users.list', new Route(
        '/api/users',
        ['_controller' => SymfonyUserController_IntegrationTest::class.'::list'],
        methods: ['GET']
    ));
    $collection->add('users.show', new Route(
        '/api/users/{id}',
        ['_controller' => SymfonyUserController_IntegrationTest::class.'::show'],
        methods: ['GET']
    ));
    $collection->add('users.create', new Route(
        '/api/users',
        ['_controller' => SymfonyUserController_IntegrationTest::class.'::create'],
        methods: ['POST']
    ));

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    expect(count($routes))->toBe(3);
});

test('Symfony routes implement RouteContract', function () {
    $route = new Route('/api/users/{id}');
    $route->setDefault('_controller', SymfonyUserController_IntegrationTest::class.'::show');

    $adapter = RouteAdapterFactory::create($route);

    expect($adapter)->toBeInstanceOf(RouteContract::class);
    expect($adapter->getUri())->toBe('/api/users/{id}');
    expect($adapter->isClassBased())->toBeTrue();
    expect($adapter->getControllerClass())->toBe(SymfonyUserController_IntegrationTest::class);
    expect($adapter->getControllerMethod())->toBe('show');
});

test('can create RouteInfo from Symfony route', function () {
    $route = new Route('/api/users/{id}');
    $route->setDefault('_controller', SymfonyUserController_IntegrationTest::class.'::show');

    $adapter = new SymfonyRouteAdapter($route, 'users.show');
    $infer = app(\Dedoc\Scramble\Infer::class);

    $routeInfo = RouteInfo::fromRouteContract($adapter, $infer);

    expect($routeInfo)->toBeInstanceOf(RouteInfo::class);
    expect($routeInfo->isClassBased())->toBeTrue();
    expect($routeInfo->className())->toBe(SymfonyUserController_IntegrationTest::class);
    expect($routeInfo->methodName())->toBe('show');
});

test('Symfony route adapter works with different HTTP methods', function () {
    $getRoute = new Route('/api/users', methods: ['GET']);
    $postRoute = new Route('/api/users', methods: ['POST']);
    $putRoute = new Route('/api/users/{id}', methods: ['PUT']);
    $deleteRoute = new Route('/api/users/{id}', methods: ['DELETE']);

    expect(RouteAdapterFactory::create($getRoute)->getMethods())->toContain('GET');
    expect(RouteAdapterFactory::create($postRoute)->getMethods())->toContain('POST');
    expect(RouteAdapterFactory::create($putRoute)->getMethods())->toContain('PUT');
    expect(RouteAdapterFactory::create($deleteRoute)->getMethods())->toContain('DELETE');
});

test('Symfony routes with parameters are parsed correctly', function () {
    $route = new Route('/api/users/{userId}/posts/{postId}');
    $adapter = RouteAdapterFactory::create($route);

    expect($adapter->getParameterNames())->toBe(['userId', 'postId']);
});

test('can filter Symfony routes by path prefix', function () {
    $collection = new RouteCollection;
    $collection->add('api.users', new Route('/api/users'));
    $collection->add('web.home', new Route('/home'));
    $collection->add('api.posts', new Route('/api/posts'));

    $provider = SymfonyRouteProvider::fromRouteCollection($collection);
    $routes = $provider->getRoutes();

    $apiRoutes = array_filter($routes, fn ($r) => str_starts_with($r->getUri(), '/api'));

    expect(count($apiRoutes))->toBe(2);
});

test('Symfony adapter preserves route metadata', function () {
    $route = new Route(
        '/api/users/{id}',
        ['_controller' => SymfonyUserController_IntegrationTest::class.'::show'],
        methods: ['GET'],
        host: 'api.example.com'
    );

    $adapter = new SymfonyRouteAdapter($route, 'users.show');

    expect($adapter->getName())->toBe('users.show');
    expect($adapter->getDomain())->toBe('api.example.com');
    expect($adapter->getMethods())->toContain('GET');
    expect($adapter->getUri())->toBe('/api/users/{id}');
});

test('RouteAdapterFactory handles mixed route types', function () {
    // Laravel route
    $laravelRoute = \Illuminate\Support\Facades\Route::get('/laravel', fn () => 'test');
    $laravelAdapter = RouteAdapterFactory::create($laravelRoute);

    // Symfony route
    $symfonyRoute = new Route('/symfony');
    $symfonyAdapter = RouteAdapterFactory::create($symfonyRoute);

    expect($laravelAdapter->getFramework())->toBe('laravel');
    expect($symfonyAdapter->getFramework())->toBe('symfony');
});

test('both frameworks support closure routes', function () {
    $closure = fn () => 'test';

    // Laravel
    $laravelRoute = \Illuminate\Support\Facades\Route::get('/test', $closure);
    $laravelAdapter = RouteAdapterFactory::create($laravelRoute);

    // Symfony
    $symfonyRoute = new Route('/test');
    $symfonyRoute->setDefault('_controller', $closure);
    $symfonyAdapter = RouteAdapterFactory::create($symfonyRoute);

    expect($laravelAdapter->isClassBased())->toBeFalse();
    expect($symfonyAdapter->isClassBased())->toBeFalse();
});
