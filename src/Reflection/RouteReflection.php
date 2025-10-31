<?php

declare(strict_types=1);

namespace Dedoc\Scramble\Reflection;

use Illuminate\Support\Str;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Routing\Route;
use WeakMap;

/**
 * Route Reflection Handler
 *
 * Provides reflection capabilities for Symfony routes.
 *
 * @internal
 */
class RouteReflection
{
    /** @var WeakMap<Route, self> */
    private static WeakMap $cache;

    private function __construct(private Route $route)
    {
    }

    public static function createFromRoute(Route $route): self
    {
        if (!isset(self::$cache)) {
            self::$cache = new WeakMap();
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
     * @return array<string, string>
     */
    public function getSignatureParametersMap(array $signatureParameters): array
    {
        $paramNames = $this->getParameterNames();
        $boundParamTypes = $this->getBoundParametersTypes($signatureParameters);

        $checkingParameters = $signatureParameters;
        $paramsToSignatureParametersNameMap = collect($paramNames)
            ->mapWithKeys(function ($name) use ($boundParamTypes, &$checkingParameters) {
                $boundParamType = $boundParamTypes[$name];

                // Find matching parameter from method signature
                $mappedParameterReflection = collect($checkingParameters)
                    ->first(function (ReflectionParameter $rp) use ($boundParamType, $name) {
                        $type = $rp->getType();

                        // Match by name first (exact or snake_case conversion)
                        if ($rp->name === $name || Str::snake($rp->name) === $name) {
                            // If builtin type or no bound type constraint, match by name
                            if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || !$boundParamType) {
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
                    $checkingParameters = array_filter(
                        $checkingParameters,
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
     * @param  array<int, ReflectionParameter>  $signatureParameters
     * @return array<string, string|null>
     */
    public function getBoundParametersTypes(array $signatureParameters): array
    {
        $paramNames = $this->getParameterNames();

        return collect($paramNames)
            ->mapWithKeys(function ($name) use ($signatureParameters) {
                // Find corresponding method parameter
                $methodParam = collect($signatureParameters)->first(
                    fn (ReflectionParameter $p) => $p->name === $name || Str::snake($p->name) === $name
                );

                if (!$methodParam) {
                    return [$name => null];
                }

                $type = $methodParam->getType();

                if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    return [$name => null];
                }

                return [$name => $type->getName()];
            })
            ->all();
    }

    /**
     * Get route parameter names from path.
     *
     * @return array<int, string>
     */
    private function getParameterNames(): array
    {
        $path = $this->route->getPath();

        preg_match_all('/\{([^}?]+)(?:\?|\})/i', $path, $matches);

        return $matches[1];
    }
}
