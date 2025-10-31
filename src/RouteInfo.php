<?php

declare(strict_types=1);

namespace Dedoc\Scramble;

use Closure;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Infer\Reflector\ClosureReflector;
use Dedoc\Scramble\Infer\Reflector\MethodReflector;
use LogicException;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\Routing\Route;

/**
 * Route Information Wrapper
 *
 * Provides unified access to Symfony route information for documentation generation.
 */
class RouteInfo
{
    protected ?Infer\Definition\FunctionLikeDefinition $actionDefinition = null;

    private ?PhpDocNode $phpDoc = null;

    private ?ClassMethod $methodNode = null;

    private ?FunctionLike $actionNode = null;

    private ?Infer\Scope\Scope $scope = null;

    public function __construct(
        public readonly Route $route,
        public readonly string $routeName,
        private Infer $infer
    ) {
    }

    public function isClassBased(): bool
    {
        $controller = $this->route->getDefault('_controller');

        return is_string($controller);
    }

    public function className(): ?string
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $controller = $this->route->getDefault('_controller');

        if (!is_string($controller)) {
            return null;
        }

        // Handle format: 'ClassName::methodName'
        if (str_contains($controller, '::')) {
            return ltrim(explode('::', $controller)[0], '\\');
        }

        // Invokable controller
        return ltrim($controller, '\\');
    }

    public function methodName(): ?string
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $controller = $this->route->getDefault('_controller');

        if (!is_string($controller)) {
            return null;
        }

        // Handle format: 'ClassName::methodName'
        if (str_contains($controller, '::')) {
            return explode('::', $controller)[1] ?? '__invoke';
        }

        // Invokable controller
        return '__invoke';
    }

    public function phpDoc(): PhpDocNode
    {
        if ($this->phpDoc) {
            return $this->phpDoc;
        }

        if (!$this->actionNode()) {
            return new PhpDocNode([]);
        }

        return $this->phpDoc = $this->actionNode()->getAttribute('parsedPhpDoc') ?: new PhpDocNode([]);
    }

    public function actionNode(): ?FunctionLike
    {
        if ($this->actionNode) {
            return $this->actionNode;
        }

        if (!$this->reflectionMethod()) {
            return null;
        }

        $methodNode = $this->getActionReflector()->getAstNode();

        if (!$methodNode instanceof ClassMethod) {
            throw new LogicException('ClassMethod node expected from method reflector');
        }

        return $this->actionNode = $methodNode;
    }

    public function reflectionAction(): ReflectionMethod|null
    {
        return $this->reflectionMethod();
    }

    public function reflectionMethod(): ?ReflectionMethod
    {
        if (!$this->isClassBased()) {
            return null;
        }

        $className = $this->className();
        $methodName = $this->methodName();

        if (!$className || !$methodName || !method_exists($className, $methodName)) {
            return null;
        }

        return (new ReflectionClass($className))->getMethod($methodName);
    }

    public function getActionReflector(): MethodReflector
    {
        if (!$this->isClassBased()) {
            throw new LogicException('Cannot get reflector for non-class-based route');
        }

        $className = $this->className();
        $methodName = $this->methodName();

        if (!$className || !$methodName) {
            throw new LogicException('Cannot determine class and method names for action reflector');
        }

        return MethodReflector::make($className, $methodName);
    }

    public function getActionDefinition(): ?Infer\Definition\FunctionLikeDefinition
    {
        if ($this->actionDefinition) {
            return $this->actionDefinition;
        }

        $this->actionDefinition = $this->getActionReflector()->getFunctionLikeDefinition(
            indexBuilders: [],
            withSideEffects: true
        );

        return $this->actionDefinition;
    }

    public function getActionType()
    {
        return $this->getActionDefinition()?->type;
    }

    /** @internal */
    public function getScope(): Infer\Scope\Scope
    {
        $this->getActionDefinition();

        if (!$this->scope) {
            throw new RuntimeException('Scope is not initialized for route.');
        }

        return $this->scope;
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
        preg_match_all('/\{([^}?]+)(?:\?|\})/i', $path, $matches);

        return $matches[1];
    }
}
