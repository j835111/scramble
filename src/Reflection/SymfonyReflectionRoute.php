<?php

namespace Dedoc\Scramble\Reflection;

use Dedoc\Scramble\Contracts\RouteContract;
use Illuminate\Support\Str;
use ReflectionNamedType;
use ReflectionParameter;
use WeakMap;

/**
 * Symfony Route Reflection Handler
 *
 * Provides reflection capabilities for Symfony routes, similar to ReflectionRoute for Laravel.
 *
 * @internal
 */
class SymfonyReflectionRoute
{
    private static WeakMap $cache;

    private function __construct(private RouteContract $route) {}

    public static function createFromRoute(RouteContract $route): static
    {
        static::$cache ??= new WeakMap;

        $originalRoute = $route->getOriginalRoute();

        return static::$cache[$originalRoute] ??= new static($route);
    }

    /**
     * Get the mapping of route parameter names to method parameter names.
     *
     * For Symfony routes like /users/{userId}/posts/{postId} with method:
     * `public function show(Request $request, string $userId, string $postId)`,
     * returns ['userId' => 'userId', 'postId' => 'postId']
     *
     * @return array<string, string>
     */
    public function getSignatureParametersMap(): array
    {
        $paramNames = $this->route->getParameterNames();
        $boundParamTypes = $this->getBoundParametersTypes();

        // Get signature parameters (method parameters)
        $signatureParameters = $this->route->getSignatureParameters();

        $paramsToSignatureParametersNameMap = collect($paramNames)
            ->mapWithKeys(function ($name) use ($boundParamTypes, &$signatureParameters) {
                $boundParamType = $boundParamTypes[$name];

                // Find matching parameter from method signature
                $mappedParameterReflection = collect($signatureParameters)
                    ->first(function (ReflectionParameter $rp) use ($boundParamType, $name) {
                        $type = $rp->getType();

                        // Match by name first (exact or snake_case conversion)
                        if ($rp->name === $name || Str::snake($rp->name) === $name) {
                            // If builtin type or no bound type constraint, match by name
                            if (! $type instanceof ReflectionNamedType || $type->isBuiltin() || ! $boundParamType) {
                                return true;
                            }

                            // If there's a type constraint, verify it matches
                            $className = $type->getName();

                            return is_a($boundParamType, $className, true);
                        }

                        return false;
                    });

                if ($mappedParameterReflection) {
                    // Remove matched parameter from available list
                    $signatureParameters = array_filter(
                        $signatureParameters,
                        fn ($v) => $v !== $mappedParameterReflection
                    );
                }

                return [$name => $mappedParameterReflection];
            });

        return $paramsToSignatureParametersNameMap
            ->mapWithKeys(fn (?ReflectionParameter $reflectionParameter, $name) => [
                $name => $reflectionParameter?->name ?: $name,
            ])
            ->all();
    }

    /**
     * Get bound parameter types.
     *
     * For Symfony, this includes:
     * - Entity parameters (via ParamConverter or route requirements)
     * - Enum parameters (backed enums)
     * - Custom route requirements with type hints
     *
     * @return array<string, string|null>
     */
    public function getBoundParametersTypes(): array
    {
        $paramNames = $this->route->getParameterNames();
        $signatureParameters = $this->route->getSignatureParameters();

        return collect($paramNames)
            ->mapWithKeys(function ($name) use ($signatureParameters) {
                // Find corresponding method parameter
                $methodParam = collect($signatureParameters)->first(
                    fn (ReflectionParameter $p) => $p->name === $name || Str::snake($p->name) === $name
                );

                if (! $methodParam) {
                    return [$name => null];
                }

                $type = $methodParam->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    return [$name => null];
                }

                return [$name => $type->getName()];
            })
            ->all();
    }
}
