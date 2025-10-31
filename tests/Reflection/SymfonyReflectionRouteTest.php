<?php

namespace Dedoc\Scramble\Tests\Reflection;

use Dedoc\Scramble\Adapters\SymfonyRouteAdapter;
use Dedoc\Scramble\Reflection\SymfonyReflectionRoute;
use Symfony\Component\Routing\Route;

// Test controller for reflection tests
class SymfonyTestController_ReflectionTest
{
    public function show(int $id): array
    {
        return ['id' => $id];
    }

    public function update(int $userId, string $postId): array
    {
        return ['userId' => $userId, 'postId' => $postId];
    }

    public function withSnakeCase(int $user_id, string $post_id): array
    {
        return ['user_id' => $user_id, 'post_id' => $post_id];
    }
}

test('creates single instance from route', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', SymfonyTestController_ReflectionTest::class.'::show');
    $adapter = new SymfonyRouteAdapter($route);

    $ra = SymfonyReflectionRoute::createFromRoute($adapter);
    $rb = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($ra === $rb)->toBeTrue();
});

test('gets params aliases from route', function () {
    $route = new Route('/users/{id}');
    $route->setDefault('_controller', SymfonyTestController_ReflectionTest::class.'::show');
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($reflection->getSignatureParametersMap())->toBe([
        'id' => 'id',
    ]);
});

test('gets params aliases with multiple parameters', function () {
    $route = new Route('/users/{userId}/posts/{postId}');
    $route->setDefault('_controller', SymfonyTestController_ReflectionTest::class.'::update');
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($reflection->getSignatureParametersMap())->toBe([
        'userId' => 'userId',
        'postId' => 'postId',
    ]);
});

test('handles snake_case to camelCase parameter mapping', function () {
    $route = new Route('/users/{user_id}/posts/{post_id}');
    $route->setDefault('_controller', SymfonyTestController_ReflectionTest::class.'::withSnakeCase');
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($reflection->getSignatureParametersMap())->toBe([
        'user_id' => 'user_id',
        'post_id' => 'post_id',
    ]);
});

test('gets bound parameters types', function () {
    $route = new Route('/users/{id}');
    $route->setDefault('_controller', SymfonyTestController_ReflectionTest::class.'::show');
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);
    $types = $reflection->getBoundParametersTypes();

    expect($types)->toHaveKey('id');
    expect($types['id'])->toBeNull(); // int is builtin type
});

test('returns null for non-existent parameters', function () {
    $route = new Route('/test');
    $route->setDefault('_controller', SymfonyTestController_ReflectionTest::class.'::show');
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($reflection->getBoundParametersTypes())->toBe([]);
});

test('handles routes with no controller', function () {
    $route = new Route('/test');
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($reflection->getSignatureParametersMap())->toBe([]);
    expect($reflection->getBoundParametersTypes())->toBe([]);
});

test('handles routes with closure controller', function () {
    $route = new Route('/users/{id}');
    $route->setDefault('_controller', fn (int $id) => ['id' => $id]);
    $adapter = new SymfonyRouteAdapter($route);

    $reflection = SymfonyReflectionRoute::createFromRoute($adapter);

    expect($reflection->getSignatureParametersMap())->toBe([]);
    expect($reflection->getBoundParametersTypes())->toBe([]);
});
