<?php

declare(strict_types=1);

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Reflection\RouteReflection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

class SampleController
{
    public function simpleMethod(int $id): void
    {
    }

    public function camelCaseMethod(int $userId, string $postId): void
    {
    }

    public function mixedParameters(Product $product, int $id, string $name): void
    {
    }

    public function snakeCaseParams(int $user_id, string $post_title): void
    {
    }
}

class Product
{
    public function __construct(public int $id)
    {
    }
}

class RouteReflectionTest extends TestCase
{
    public function testCreateFromRouteReturnsSameInstanceForSameRoute(): void
    {
        $route = new Route('/test/{id}');

        $reflection1 = RouteReflection::createFromRoute($route);
        $reflection2 = RouteReflection::createFromRoute($route);

        $this->assertSame($reflection1, $reflection2, 'Should return cached instance for same route');
    }

    public function testCreateFromRouteReturnsDifferentInstancesForDifferentRoutes(): void
    {
        $route1 = new Route('/test1/{id}');
        $route2 = new Route('/test2/{id}');

        $reflection1 = RouteReflection::createFromRoute($route1);
        $reflection2 = RouteReflection::createFromRoute($route2);

        $this->assertNotSame($reflection1, $reflection2, 'Should return different instances for different routes');
    }

    public function testGetSignatureParametersMapWithSimpleParameter(): void
    {
        $route      = new Route('/users/{id}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'simpleMethod');
        $params = $method->getParameters();

        $mapping = $reflection->getSignatureParametersMap($params);

        $this->assertEquals(['id' => 'id'], $mapping);
    }

    public function testGetSignatureParametersMapWithCamelCaseConversion(): void
    {
        $route      = new Route('/users/{user_id}/posts/{post_id}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'camelCaseMethod');
        $params = $method->getParameters();

        $mapping = $reflection->getSignatureParametersMap($params);

        $this->assertEquals([
            'user_id' => 'userId',
            'post_id' => 'postId',
        ], $mapping);
    }

    public function testGetSignatureParametersMapWithMultipleParameters(): void
    {
        $route      = new Route('/products/{product}/items/{id}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'mixedParameters');
        $params = $method->getParameters();

        $mapping = $reflection->getSignatureParametersMap($params);

        $this->assertArrayHasKey('product', $mapping);
        $this->assertArrayHasKey('id', $mapping);
        $this->assertEquals('product', $mapping['product']);
        $this->assertEquals('id', $mapping['id']);
    }

    public function testGetSignatureParametersMapWithSnakeCaseRouteParams(): void
    {
        $route      = new Route('/users/{user_id}/posts/{post_title}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'snakeCaseParams');
        $params = $method->getParameters();

        $mapping = $reflection->getSignatureParametersMap($params);

        $this->assertEquals([
            'user_id'    => 'user_id',
            'post_title' => 'post_title',
        ], $mapping);
    }

    public function testGetBoundParametersTypesWithBuiltinTypes(): void
    {
        $route      = new Route('/users/{id}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'simpleMethod');
        $params = $method->getParameters();

        $types = $reflection->getBoundParametersTypes($params);

        $this->assertEquals(['id' => null], $types, 'Builtin types should return null');
    }

    public function testGetBoundParametersTypesWithObjectType(): void
    {
        $route      = new Route('/products/{product}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'mixedParameters');
        $params = $method->getParameters();

        $types = $reflection->getBoundParametersTypes($params);

        $this->assertArrayHasKey('product', $types);
        $this->assertEquals(Product::class, $types['product']);
    }

    public function testGetBoundParametersTypesWithMixedTypes(): void
    {
        $route      = new Route('/products/{product}/items/{id}/names/{name}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'mixedParameters');
        $params = $method->getParameters();

        $types = $reflection->getBoundParametersTypes($params);

        $this->assertEquals(Product::class, $types['product'], 'Object type should be detected');
        $this->assertNull($types['id'], 'Builtin int should be null');
        $this->assertNull($types['name'], 'Builtin string should be null');
    }

    public function testGetBoundParametersTypesWithNonExistentParameter(): void
    {
        $route      = new Route('/users/{nonexistent}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'simpleMethod');
        $params = $method->getParameters();

        $types = $reflection->getBoundParametersTypes($params);

        $this->assertEquals(['nonexistent' => null], $types);
    }

    public function testGetSignatureParametersMapPreservesOrderWhenNoMatch(): void
    {
        $route      = new Route('/test/{first}/{second}');
        $reflection = RouteReflection::createFromRoute($route);

        $method = new \ReflectionMethod(SampleController::class, 'simpleMethod');
        $params = $method->getParameters();

        $mapping = $reflection->getSignatureParametersMap($params);

        // When parameters don't match method signature, should preserve route param names
        $this->assertArrayHasKey('first', $mapping);
        $this->assertArrayHasKey('second', $mapping);
    }
}
