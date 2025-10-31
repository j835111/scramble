<?php

/**
 * Example: Using Scramble with Symfony
 *
 * This example demonstrates how to integrate Scramble's OpenAPI documentation
 * generator with a Symfony application.
 */

namespace App\Examples;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Providers\SymfonyRouteProvider;
use Dedoc\Scramble\Support\OperationBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

/**
 * Example Symfony Controller with API endpoints
 */
class UserController
{
    #[Route('/api/users', name: 'users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ]);
    }

    #[Route('/api/users/{id}', name: 'users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return new JsonResponse([
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Route('/api/users', name: 'users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return new JsonResponse([
            'id' => 3,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? '',
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/users/{id}', name: 'users_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return new JsonResponse([
            'id' => $id,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? '',
        ]);
    }

    #[Route('/api/users/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}

/**
 * Integration Example: Generate OpenAPI Documentation
 */
class ScrambleSymfonyIntegration
{
    private Router $router;

    private Generator $generator;

    public function __construct(Router $router)
    {
        $this->router = $router;

        // Initialize Scramble components
        $infer = new Infer;
        $operationBuilder = new OperationBuilder;
        $this->generator = new Generator($operationBuilder, $infer);
    }

    /**
     * Generate OpenAPI specification from Symfony routes.
     *
     * Usage in a Symfony controller:
     *
     * ```php
     * #[Route('/api/docs.json', name: 'api_docs', methods: ['GET'])]
     * public function apiDocs(Router $router): JsonResponse
     * {
     *     $integration = new ScrambleSymfonyIntegration($router);
     *     $openApiSpec = $integration->generateDocumentation();
     *
     *     return new JsonResponse($openApiSpec);
     * }
     * ```
     */
    public function generateDocumentation(array $config = []): array
    {
        // Create route provider for Symfony
        $routeProvider = new SymfonyRouteProvider($this->router);

        // Get configuration
        $generatorConfig = $this->createConfig($config);

        // Generate OpenAPI specification
        return ($this->generator)($generatorConfig);
    }

    /**
     * Create Scramble configuration for Symfony.
     */
    private function createConfig(array $config): GeneratorConfig
    {
        $generatorConfig = new GeneratorConfig;

        // Set basic API information
        $generatorConfig->set('ui.title', $config['title'] ?? 'API Documentation');
        $generatorConfig->set('info.version', $config['version'] ?? '1.0.0');
        $generatorConfig->set('info.description', $config['description'] ?? '');

        // Set API path prefix (default to 'api')
        $generatorConfig->set('api_path', $config['api_path'] ?? 'api');

        // Set API domain if needed
        if (isset($config['api_domain'])) {
            $generatorConfig->set('api_domain', $config['api_domain']);
        }

        // Set servers
        if (isset($config['servers'])) {
            $generatorConfig->set('servers', $config['servers']);
        }

        // Route filtering for Symfony
        $generatorConfig->routes(function ($route) use ($config) {
            // Use RouteContract interface methods
            $uri = $route->getUri();
            $prefix = $config['api_path'] ?? 'api';

            // Filter routes by prefix
            return str_starts_with($uri, '/'.$prefix) || str_starts_with($uri, $prefix);
        });

        return $generatorConfig;
    }

    /**
     * Alternative: Generate documentation from a RouteCollection.
     *
     * Useful when you want to generate docs for a subset of routes:
     *
     * ```php
     * $routeCollection = new RouteCollection();
     * // Add your routes...
     *
     * $integration = ScrambleSymfonyIntegration::fromRouteCollection($routeCollection);
     * $openApiSpec = $integration->generateDocumentation();
     * ```
     */
    public static function fromRouteCollection(RouteCollection $routeCollection): self
    {
        $router = null; // Will use route collection directly
        $instance = new self($router);

        // Override the route provider
        $routeProvider = SymfonyRouteProvider::fromRouteCollection($routeCollection);

        return $instance;
    }
}

/**
 * Example: Symfony Controller for serving OpenAPI documentation
 */
class ApiDocumentationController
{
    #[Route('/api/docs.json', name: 'api_docs_json', methods: ['GET'])]
    public function apiDocsJson(Router $router): JsonResponse
    {
        $integration = new ScrambleSymfonyIntegration($router);

        $openApiSpec = $integration->generateDocumentation([
            'title' => 'My API',
            'version' => '1.0.0',
            'description' => 'API documentation generated by Scramble',
            'api_path' => 'api',
        ]);

        return new JsonResponse($openApiSpec);
    }

    #[Route('/api/docs', name: 'api_docs_ui', methods: ['GET'])]
    public function apiDocsUi(): Response
    {
        // Serve a UI viewer (e.g., Swagger UI, ReDoc, Stoplight Elements)
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>API Documentation</title>
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>
<body>
    <elements-api
        apiDescriptionUrl="/api/docs.json"
        router="hash"
        layout="sidebar"
    />
</body>
</html>
HTML;

        return new Response($html);
    }
}
