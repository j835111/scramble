<?php

declare(strict_types=1);

namespace Dedoc\Scramble;

use Symfony\Component\Routing\Route;

/**
 * Route Information Wrapper
 *
 * Provides unified access to Symfony route information for documentation generation.
 */
class RouteInfo
{
    public function __construct(
        public readonly Route $route,
        public readonly string $routeName
    ) {
    }

    public function isClassBased(): bool
    {
        $controller = $this->route->getDefault('_controller');

        return \is_string($controller);
    }

    public function className(): ?string
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $controller = $this->route->getDefault('_controller');

        if (!\is_string($controller)) {
            return null;
        }

        // Handle format: 'ClassName::methodName'
        if (\str_contains($controller, '::')) {
            return \ltrim(\explode('::', $controller)[0], '\\');
        }

        // Invokable controller
        return \ltrim($controller, '\\');
    }

    public function methodName(): ?string
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $controller = $this->route->getDefault('_controller');

        if (!\is_string($controller)) {
            return null;
        }

        // Handle format: 'ClassName::methodName'
        if (\str_contains($controller, '::')) {
            return \explode('::', $controller)[1] ?? '__invoke';
        }

        // Invokable controller
        return '__invoke';
    }

    public function reflectionMethod(): ?\ReflectionMethod
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $className  = $this->className();
        $methodName = $this->methodName();

        if (!$className || !$methodName || !\class_exists($className) || !\method_exists($className, $methodName)) {
            return null;
        }

        return (new \ReflectionClass($className))->getMethod($methodName);
    }

    /**
     * Get HTTP methods for this route.
     *
     * @return array<int, string>
     */
    public function getMethods(): array
    {
        $methods = $this->route->getMethods();

        // If no methods specified, Symfony allows all methods
        if (empty($methods)) {
            return ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
        }

        return $methods;
    }

    /**
     * Get the URI path for this route.
     */
    public function getPath(): string
    {
        return $this->route->getPath();
    }

    /**
     * Get route parameter names.
     *
     * @return array<int, string>
     */
    public function getParameterNames(): array
    {
        $path = $this->route->getPath();

        // Extract parameters from path
        \preg_match_all('/\{([^}?]+)(?:\?|\})/i', $path, $matches);

        return $matches[1];
    }
}
