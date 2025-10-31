<?php

namespace Dedoc\Scramble\Contracts;

use Closure;
use ReflectionMethod;

/**
 * Route abstraction contract to support multiple frameworks.
 * This interface provides a common API for accessing route information
 * regardless of the underlying framework (Laravel, Symfony, etc.)
 */
interface RouteContract
{
    /**
     * Get the HTTP methods for the route.
     *
     * @return array<int, string>
     */
    public function getMethods(): array;

    /**
     * Get the URI/path pattern for the route.
     * Example: /users/{id}/posts/{post}
     */
    public function getUri(): string;

    /**
     * Get the action (controller method or closure) for the route.
     * Returns string for class-based routes (e.g., 'App\Controllers\UserController@show')
     * Returns Closure for closure-based routes.
     *
     * @return string|Closure|array<string, mixed>|null
     */
    public function getAction();

    /**
     * Get specific action attribute.
     *
     * @return mixed
     */
    public function getActionAttribute(string $key);

    /**
     * Check if the route is class-based (not a closure).
     */
    public function isClassBased(): bool;

    /**
     * Get the controller class name.
     */
    public function getControllerClass(): ?string;

    /**
     * Get the controller method name.
     */
    public function getControllerMethod(): ?string;

    /**
     * Get route parameter names defined in the path.
     * Example: For /users/{id}/posts/{post}, returns ['id', 'post']
     *
     * @return array<int, string>
     */
    public function getParameterNames(): array;

    /**
     * Get the name of the route if it has one.
     */
    public function getName(): ?string;

    /**
     * Get the domain for the route, if any.
     */
    public function getDomain(): ?string;

    /**
     * Get the signature parameters (method parameters) for the route action.
     * These are the parameters from the ReflectionMethod/ReflectionClosure.
     *
     * @param  string|array<string, mixed>|null  $subClass
     * @return array<int, \ReflectionParameter>
     */
    public function getSignatureParameters($subClass = null): array;

    /**
     * Get the reflection method for class-based routes.
     */
    public function getReflectionMethod(): ?ReflectionMethod;

    /**
     * Get the underlying framework-specific route object.
     * This allows access to framework-specific features when needed.
     *
     * @return mixed
     */
    public function getOriginalRoute();

    /**
     * Get the framework identifier (e.g., 'laravel', 'symfony').
     */
    public function getFramework(): string;
}
