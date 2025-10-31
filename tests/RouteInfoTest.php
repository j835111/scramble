<?php

declare(strict_types=1);

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\RouteInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

class TestController
{
    /**
     * Show user details
     *
     * @param int $id User ID
     *
     * @return array
     */
    public function show(int $id): array
    {
        return ['id' => $id];
    }

    public function list(): array
    {
        return [['id' => 1], ['id' => 2]];
    }

    public function __invoke(): array
    {
        return [];
    }
}

class RouteInfoTest extends TestCase
{
    private Infer $infer;

    protected function setUp(): void
    {
        $this->infer = new Infer();
    }

    public function testIdentifiesClassBasedRoutes(): void
    {
        $route = new Route('/users/{id}');
        $route->setDefault('_controller', TestController::class.'::show');

        $routeInfo = new RouteInfo($route, 'users.show', $this->infer);

        $this->assertTrue($routeInfo->isClassBased());
        $this->assertEquals(TestController::class, $routeInfo->className());
        $this->assertEquals('show', $routeInfo->methodName());
    }

    public function testHandlesInvokableControllers(): void
    {
        $route = new Route('/users');
        $route->setDefault('_controller', TestController::class);

        $routeInfo = new RouteInfo($route, 'users', $this->infer);

        $this->assertTrue($routeInfo->isClassBased());
        $this->assertEquals(TestController::class, $routeInfo->className());
        $this->assertEquals('__invoke', $routeInfo->methodName());
    }

    public function testGetsMethods(): void
    {
        $route = new Route('/users', methods: ['GET', 'POST']);

        $routeInfo = new RouteInfo($route, 'users', $this->infer);

        $methods = $routeInfo->getMethods();
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
    }

    public function testGetsPath(): void
    {
        $route = new Route('/users/{id}/posts/{postId}');

        $routeInfo = new RouteInfo($route, 'test', $this->infer);

        $this->assertEquals('/users/{id}/posts/{postId}', $routeInfo->getPath());
    }

    public function testGetsParameterNames(): void
    {
        $route = new Route('/users/{userId}/posts/{postId}');

        $routeInfo = new RouteInfo($route, 'test', $this->infer);

        $params = $routeInfo->getParameterNames();
        $this->assertEquals(['userId', 'postId'], $params);
    }

    public function testGetsReflectionMethod(): void
    {
        $route = new Route('/users/{id}');
        $route->setDefault('_controller', TestController::class.'::show');

        $routeInfo = new RouteInfo($route, 'users.show', $this->infer);

        $reflection = $routeInfo->reflectionMethod();

        $this->assertNotNull($reflection);
        $this->assertEquals('show', $reflection->getName());
        $this->assertEquals(1, $reflection->getNumberOfParameters());
    }
}
