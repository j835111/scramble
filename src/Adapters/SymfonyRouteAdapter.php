<?php

namespace Dedoc\Scramble\Adapters;

use Closure;
use Dedoc\Scramble\Contracts\RouteContract;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Routing\Route;

/**
 * Symfony Route Adapter
 *
 * Wraps Symfony's Route to provide a framework-agnostic interface.
 */
class SymfonyRouteAdapter implements RouteContract
{
    private ?string $routeName = null;

    public function __construct(
        private Route $route,
        ?string $routeName = null
    ) {
        $this->routeName = $routeName;
    }

    public function getMethods(): array
    {
        $methods = $this->route->getMethods();

        // If no methods are specified, Symfony allows all methods
        if (empty($methods)) {
            return ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
        }

        return $methods;
    }

    public function getUri(): string
    {
        return $this->route->getPath();
    }

    public function getAction()
    {
        $controller = $this->route->getDefault('_controller');

        if (! $controller) {
            return null;
        }

        // Symfony controller can be:
        // 1. String: 'App\Controller\UserController::show'
        // 2. Array: ['App\Controller\UserController', 'show']
        // 3. Closure
        if (is_string($controller) && str_contains($controller, '::')) {
            // Convert Symfony format (::) to Laravel format (@) for consistency
            return str_replace('::', '@', $controller);
        }

        if (is_array($controller) && count($controller) === 2) {
            return $controller[0].'@'.$controller[1];
        }

        if ($controller instanceof Closure) {
            return $controller;
        }

        // For invokable controllers like 'App\Controller\UserController'
        if (is_string($controller) && class_exists($controller)) {
            return $controller.'@__invoke';
        }

        return $controller;
    }

    public function getActionAttribute(string $key)
    {
        // Map common attributes
        if ($key === 'uses') {
            return $this->getAction();
        }

        // For other attributes, check route defaults
        return $this->route->getDefault($key);
    }

    public function isClassBased(): bool
    {
        $action = $this->getAction();

        return is_string($action) && ! $action instanceof Closure;
    }

    public function getControllerClass(): ?string
    {
        if (! $this->isClassBased()) {
            return null;
        }

        $action = $this->getAction();

        if (! is_string($action)) {
            return null;
        }

        // Handle format: 'ClassName@methodName' or 'ClassName::methodName'
        if (str_contains($action, '@')) {
            return ltrim(explode('@', $action)[0], '\\');
        }

        if (str_contains($action, '::')) {
            return ltrim(explode('::', $action)[0], '\\');
        }

        // Invokable controller
        return ltrim($action, '\\');
    }

    public function getControllerMethod(): ?string
    {
        if (! $this->isClassBased()) {
            return null;
        }

        $action = $this->getAction();

        if (! is_string($action)) {
            return null;
        }

        // Handle format: 'ClassName@methodName'
        if (str_contains($action, '@')) {
            return explode('@', $action)[1] ?? '__invoke';
        }

        // Handle format: 'ClassName::methodName'
        if (str_contains($action, '::')) {
            return explode('::', $action)[1] ?? '__invoke';
        }

        // Invokable controller
        return '__invoke';
    }

    public function getParameterNames(): array
    {
        $path = $this->route->getPath();

        // Extract parameters from Symfony route path
        // Example: /users/{id}/posts/{postId} -> ['id', 'postId']
        preg_match_all('/\{([^}?]+)(?:\?|\})/i', $path, $matches);

        return $matches[1] ?? [];
    }

    public function getName(): ?string
    {
        return $this->routeName;
    }

    public function getDomain(): ?string
    {
        return $this->route->getHost();
    }

    public function getSignatureParameters($subClass = null): array
    {
        $reflectionMethod = $this->getReflectionMethod();

        if (! $reflectionMethod) {
            return [];
        }

        $parameters = $reflectionMethod->getParameters();

        // If no filtering is needed, return all parameters
        if ($subClass === null) {
            return $parameters;
        }

        // Filter parameters by class type
        return array_filter($parameters, function ($parameter) use ($subClass) {
            $type = $parameter->getType();

            if (! $type || $type->isBuiltin()) {
                return false;
            }

            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

            if (! $typeName) {
                return false;
            }

            // Handle array format like ['backedEnum' => true]
            if (is_array($subClass) && isset($subClass['backedEnum']) && $subClass['backedEnum']) {
                try {
                    $reflectionClass = new ReflectionClass($typeName);

                    return $reflectionClass->isEnum() && method_exists($typeName, 'cases');
                } catch (ReflectionException) {
                    return false;
                }
            }

            // Handle class/interface name filtering
            if (is_string($subClass)) {
                return is_a($typeName, $subClass, true);
            }

            return false;
        });
    }

    public function getReflectionMethod(): ?ReflectionMethod
    {
        if (! $this->isClassBased()) {
            return null;
        }

        $class = $this->getControllerClass();
        $method = $this->getControllerMethod();

        if (! $class || ! $method) {
            return null;
        }

        try {
            if (! class_exists($class)) {
                return null;
            }

            if (! method_exists($class, $method)) {
                return null;
            }

            return (new ReflectionClass($class))->getMethod($method);
        } catch (ReflectionException) {
            return null;
        }
    }

    public function getOriginalRoute(): Route
    {
        return $this->route;
    }

    public function getFramework(): string
    {
        return 'symfony';
    }

    /**
     * Create an adapter instance from a Symfony route.
     */
    public static function fromRoute(Route $route, ?string $routeName = null): self
    {
        return new self($route, $routeName);
    }
}
