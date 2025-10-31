# Symfony Integration Guide

This guide explains how to use Scramble with Symfony applications to generate OpenAPI documentation automatically.

## Overview

Scramble now supports Symfony routing in addition to Laravel. The integration uses the same core engine but adapts to Symfony's routing system through a clean adapter pattern.

## Features

- **Automatic OpenAPI Generation**: Generate OpenAPI 3.1.0 specifications from your Symfony routes
- **Multi-Framework Support**: Same powerful features as Laravel integration
- **Type Inference**: Automatically infer request/response types from controller methods
- **Route Attributes**: Full support for Symfony's route attributes
- **Parameter Detection**: Automatic detection of path, query, and request body parameters

## Installation

```bash
composer require dedoc/scramble
```

## Basic Usage

### 1. Create a Documentation Controller

```php
<?php

namespace App\Controller;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Providers\SymfonyRouteProvider;
use Dedoc\Scramble\Support\OperationBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class ApiDocController extends AbstractController
{
    #[Route('/api/docs.json', name: 'api_docs', methods: ['GET'])]
    public function apiDocs(RouterInterface $router): JsonResponse
    {
        // Initialize Scramble components
        $infer = new Infer();
        $operationBuilder = new OperationBuilder();
        $generator = new Generator($operationBuilder, $infer);

        // Create Symfony route provider
        $routeProvider = new SymfonyRouteProvider($router);

        // Configure generator
        $config = new GeneratorConfig();
        $config->set('ui.title', 'My Symfony API');
        $config->set('info.version', '1.0.0');
        $config->set('info.description', 'API Documentation');
        $config->set('api_path', 'api');

        // Filter routes (only include API routes)
        $config->routes(function ($route) {
            return str_starts_with($route->getUri(), '/api');
        });

        // Generate OpenAPI specification
        $openApiSpec = $generator($config);

        return new JsonResponse($openApiSpec);
    }

    #[Route('/api/docs', name: 'api_docs_ui', methods: ['GET'])]
    public function apiDocsUi(): Response
    {
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
```

### 2. Create Your API Controllers

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    /**
     * List all users
     *
     * @return JsonResponse
     */
    #[Route('', name: 'users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        return $this->json($users);
    }

    /**
     * Get a specific user
     *
     * @param int $id User ID
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json([
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    /**
     * Create a new user
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('', name: 'users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return $this->json([
            'id' => 3,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? '',
        ], 201);
    }

    /**
     * Update a user
     *
     * @param int $id User ID
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'users_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return $this->json([
            'id' => $id,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? '',
        ]);
    }

    /**
     * Delete a user
     *
     * @param int $id User ID
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->json(null, 204);
    }
}
```

### 3. Access Your Documentation

Visit your application:
- JSON specification: `http://localhost:8000/api/docs.json`
- UI documentation: `http://localhost:8000/api/docs`

## Advanced Configuration

### Custom Route Filtering

Filter which routes are included in the documentation:

```php
$config->routes(function ($route) {
    // Only include routes under /api
    if (!str_starts_with($route->getUri(), '/api')) {
        return false;
    }

    // Exclude admin routes
    if (str_contains($route->getUri(), '/admin')) {
        return false;
    }

    // Only include GET and POST methods
    $methods = $route->getMethods();
    return in_array('GET', $methods) || in_array('POST', $methods);
});
```

### Multiple API Versions

Generate different documentation for different API versions:

```php
// API v1
$configV1 = new GeneratorConfig();
$configV1->set('api_path', 'api/v1');
$configV1->routes(fn($route) => str_starts_with($route->getUri(), '/api/v1'));

// API v2
$configV2 = new GeneratorConfig();
$configV2->set('api_path', 'api/v2');
$configV2->routes(fn($route) => str_starts_with($route->getUri(), '/api/v2'));
```

### Server Configuration

Define multiple servers:

```php
$config->set('servers', [
    'Development' => 'http://localhost:8000',
    'Staging' => 'https://staging.example.com',
    'Production' => 'https://api.example.com',
]);
```

## Type Inference

Scramble automatically infers types from your controller methods:

```php
class ProductController extends AbstractController
{
    /**
     * Get products list
     *
     * @return JsonResponse<array<Product>>
     */
    #[Route('/api/products', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Return type will be inferred from PHPDoc
        return $this->json($this->productRepository->findAll());
    }
}
```

## Working with Entities

Scramble can understand Doctrine entities:

```php
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $price;

    // Getters and setters...
}

class ProductController extends AbstractController
{
    #[Route('/api/products/{id}', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        // Scramble will detect the Product entity parameter
        return $this->json($product);
    }
}
```

## Architecture

The Symfony integration uses a clean adapter pattern:

```
Symfony Route → SymfonyRouteAdapter → RouteContract → Scramble Core
```

### Key Components

1. **RouteContract**: Common interface for all route types
2. **SymfonyRouteAdapter**: Adapts Symfony routes to RouteContract
3. **SymfonyRouteProvider**: Collects routes from Symfony router
4. **SymfonyReflectionRoute**: Handles route reflection for Symfony
5. **RouteAdapterFactory**: Factory for creating route adapters

## Comparison with Laravel

| Feature | Laravel | Symfony |
|---------|---------|---------|
| Route Source | `Route` facade | `RouterInterface` |
| Route Format | `Controller@method` | `Controller::method` or attributes |
| Route Attributes | Native support | Native support |
| Parameter Binding | Eloquent models | Doctrine entities |
| Validation | Form Requests | Constraints/Validators |
| Closure Routes | Supported | Supported |

## Troubleshooting

### Routes Not Appearing

Check that:
1. Routes are registered in your Symfony router
2. Route filtering in config includes your routes
3. Routes have controller classes (not closures for production)

### Type Inference Issues

Ensure:
1. PHPDoc comments are properly formatted
2. Return types are declared on methods
3. Entity classes are accessible and properly typed

### Performance

For large applications:
- Cache the generated OpenAPI specification
- Filter routes to only include API endpoints
- Use production mode to disable debug features

## Contributing

Contributions are welcome! The Symfony integration follows the same patterns as Laravel:

1. Create adapters for framework-specific features
2. Implement the RouteContract interface
3. Add tests for new functionality
4. Update documentation

## License

MIT License - see LICENSE file for details
