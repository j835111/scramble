<?php

declare(strict_types=1);

namespace Dedoc\Scramble\Reflection;

use Symfony\Component\Routing\Route;

/**
 * Route Reflection Handler
 *
 * Provides reflection capabilities for Symfony routes.
 *
 * @internal
 */
class RouteReflection
{
    /** @var \WeakMap<Route, self> */
    private static \WeakMap $cache;

    private function __construct(private Route $route)
    {
    }

    public static function createFromRoute(Route $route): self
    {
        if (!isset(self::$cache)) {
            self::$cache = new \WeakMap();
        }

        if (!isset(self::$cache[$route])) {
            self::$cache[$route] = new self($route);
        }

        return self::$cache[$route];
    }

    /**
     * Get the mapping of route parameter names to method parameter names.
     *
     * For routes like /users/{userId}/posts/{postId} with method:
     * `public function show(Request $request, string $userId, string $postId)`,
     * returns ['userId' => 'userId', 'postId' => 'postId']
     *
     * @param array<int, \ReflectionParameter> $signatureParameters
     * @return array<string, string>
     */
    public function getSignatureParametersMap(array $signatureParameters): array
    {
        $paramNames      = $this->getParameterNames();
        $boundParamTypes = $this->getBoundParametersTypes($signatureParameters);

        $checkingParameters                 = $signatureParameters;
        $paramsToSignatureParametersNameMap = [];

        foreach ($paramNames as $name) {
            $boundParamType = $boundParamTypes[$name];

            // Find matching parameter from method signature
            $mappedParameterReflection = null;
            foreach ($checkingParameters as $rp) {
                $type = $rp->getType();

                // Match by name first (exact or snake_case conversion)
                if ($rp->name === $name || $this->toSnakeCase($rp->name) === $name) {
                    // If builtin type or no bound type constraint, match by name
                    if (!$type instanceof \ReflectionNamedType || $type->isBuiltin() || !$boundParamType) {
                        $mappedParameterReflection = $rp;
                        break;
                    }

                    // If there's a type constraint, verify it matches
                    $className = $type->getName();

                    if (\is_a($boundParamType, $className, true)) {
                        $mappedParameterReflection = $rp;
                        break;
                    }
                }
            }

            if ($mappedParameterReflection) {
                // Remove matched parameter from available list
                $checkingParameters = \array_filter(
                    $checkingParameters,
                    fn ($v) => $v !== $mappedParameterReflection
                );
            }

            $paramsToSignatureParametersNameMap[$name] = $mappedParameterReflection;
        }

        // Map to parameter names
        $result = [];
        foreach ($paramsToSignatureParametersNameMap as $name => $reflectionParameter) {
            $result[$name] = $reflectionParameter?->name ?: $name;
        }

        return $result;
    }

    /**
     * Get bound parameter types.
     *
     * @param array<int, \ReflectionParameter> $signatureParameters
     * @return array<string, string|null>
     */
    public function getBoundParametersTypes(array $signatureParameters): array
    {
        $paramNames = $this->getParameterNames();
        $result     = [];

        foreach ($paramNames as $name) {
            // Find corresponding method parameter
            $methodParam = null;
            foreach ($signatureParameters as $p) {
                if ($p->name === $name || $this->toSnakeCase($p->name) === $name) {
                    $methodParam = $p;
                    break;
                }
            }

            if (!$methodParam) {
                $result[$name] = null;
                continue;
            }

            $type = $methodParam->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                $result[$name] = null;
                continue;
            }

            $result[$name] = $type->getName();
        }

        return $result;
    }

    /**
     * Get route parameter names from path.
     *
     * @return array<int, string>
     */
    private function getParameterNames(): array
    {
        $path = $this->route->getPath();

        \preg_match_all('/\{([^}?]+)(?:\?|\})/i', $path, $matches);

        return $matches[1];
    }

    /**
     * Convert a string to snake_case.
     */
    private function toSnakeCase(string $value): string
    {
        if (!\ctype_lower($value)) {
            $value = \preg_replace('/\s+/u', '', \ucwords($value)) ?? $value;
            $value = \strtolower(\preg_replace('/(.)(?=[A-Z])/u', '$1_', $value) ?? $value);
        }

        return $value;
    }
}
