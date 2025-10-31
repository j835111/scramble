<?php

namespace Dedoc\Scramble\Adapters;

use Dedoc\Scramble\Contracts\RouteContract;
use Illuminate\Routing\Route;
use ReflectionClass;
use ReflectionMethod;

/**
 * Laravel Route Adapter
 *
 * Wraps Laravel's Illuminate\Routing\Route to provide a framework-agnostic interface.
 */
class LaravelRouteAdapter implements RouteContract
{
    public function __construct(
        private Route $route
    ) {}

    public function getMethods(): array
    {
        return $this->route->methods();
    }

    public function getUri(): string
    {
        return $this->route->uri();
    }

    public function getAction()
    {
        return $this->route->getAction('uses');
    }

    public function getActionAttribute(string $key)
    {
        return $this->route->getAction($key);
    }

    public function isClassBased(): bool
    {
        return is_string($this->route->getAction('uses'));
    }

    public function getControllerClass(): ?string
    {
        if (! $this->isClassBased()) {
            return null;
        }

        $uses = $this->route->getAction('uses');

        return ltrim(explode('@', $uses)[0], '\\');
    }

    public function getControllerMethod(): ?string
    {
        if (! $this->isClassBased()) {
            return null;
        }

        $uses = $this->route->getAction('uses');

        return explode('@', $uses)[1] ?? null;
    }

    public function getParameterNames(): array
    {
        return $this->route->parameterNames();
    }

    public function getName(): ?string
    {
        return $this->route->getName();
    }

    public function getDomain(): ?string
    {
        return $this->route->getDomain();
    }

    public function getSignatureParameters($subClass = null): array
    {
        return $this->route->signatureParameters($subClass);
    }

    public function getReflectionMethod(): ?ReflectionMethod
    {
        if (! $this->isClassBased()) {
            return null;
        }

        $class = $this->getControllerClass();
        $method = $this->getControllerMethod();

        if (! $class || ! $method || ! method_exists($class, $method)) {
            return null;
        }

        return (new ReflectionClass($class))->getMethod($method);
    }

    public function getOriginalRoute(): Route
    {
        return $this->route;
    }

    public function getFramework(): string
    {
        return 'laravel';
    }

    /**
     * Create an adapter instance from a Laravel route.
     */
    public static function fromRoute(Route $route): self
    {
        return new self($route);
    }
}
