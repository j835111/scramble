# Scramble - Symfony API Documentation Generator

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://php.net)
[![Symfony](https://img.shields.io/badge/symfony-%5E6.0%7C%5E7.0-black)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Automatic OpenAPI 3.1.0 documentation generator for Symfony applications. Generate comprehensive API documentation from your Symfony routes and controllers without writing a single line of PHPDoc for documentation purposes.

## ✨ Features

- 🚀 **Zero Configuration** - Works out of the box with sensible defaults
- 📝 **Automatic Type Inference** - Infers request/response types from your code
- 🎯 **Pure Symfony** - Built specifically for Symfony, no framework overhead
- 🔍 **PHPDoc Support** - Enriches documentation with your existing comments
- ⚡ **High Performance** - Direct Symfony integration, no adapter layers
- 🎨 **OpenAPI 3.1.0** - Industry-standard API documentation format
- 🛠️ **Symfony Standards** - Follows Symfony coding standards and best practices

## 📦 Installation

Install via Composer:

```bash
composer require dedoc/scramble
```

### Requirements

- PHP 8.1 or higher
- Symfony 6.0 or 7.0
- Composer 2.x

## 🚀 Quick Start

### Step 1: Create Documentation Controller

Create a controller to serve your API documentation:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\RouteProvider;
use Dedoc\Scramble\Support\OperationBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class ApiDocController extends AbstractController
{
    #[Route('/api/docs.json', name: 'api_docs_json', methods: ['GET'])]
    public function apiDocsJson(RouterInterface $router): JsonResponse
    {
        // Initialize Scramble components
        $infer = new Infer();
        $operationBuilder = new OperationBuilder();
        $generator = new Generator($operationBuilder, $infer);

        // Get routes from Symfony router
        $routeProvider = RouteProvider::fromRouter($router);

        // Configure generator
        $config = new GeneratorConfig();
        $config->set('ui.title', 'My Symfony API');
        $config->set('info.version', '1.0.0');
        $config->set('info.description', 'API Documentation');

        // Filter only API routes
        $config->routes(function ($route, $routeName) {
            return str_starts_with($route->getPath(), '/api');
        });

        // Generate OpenAPI specification
        $openApiSpec = $generator($config);

        return new JsonResponse($openApiSpec);
    }

    #[Route('/api/docs', name: 'api_docs_ui', methods: ['GET'])]
    public function apiDocsUi(): Response
    {
        // Serve API documentation UI (using Stoplight Elements)
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

### Step 2: Create Your API Controllers

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

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
     * Returns a list of all users in the system.
     *
     * @return JsonResponse
     */
    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        return $this->json($users);
    }

    /**
     * Get user details
     *
     * Returns detailed information about a specific user.
     *
     * @param int $id The user ID
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        return $this->json([
            'id' => $id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => '2024-01-01T00:00:00Z',
        ]);
    }

    /**
     * Create a new user
     *
     * Creates a new user with the provided data.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return $this->json([
            'id' => 3,
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? '',
            'created_at' => (new \DateTime())->format(\DateTime::ATOM),
        ], 201);
    }

    /**
     * Update user details
     *
     * Updates an existing user's information.
     *
     * @param int     $id      The user ID
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_users_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        return $this->json([
            'id' => $id,
            'name' => $data['name'] ?? 'John Doe',
            'email' => $data['email'] ?? 'john@example.com',
            'updated_at' => (new \DateTime())->format(\DateTime::ATOM),
        ]);
    }

    /**
     * Delete a user
     *
     * Permanently deletes a user from the system.
     *
     * @param int $id The user ID
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        return $this->json(null, 204);
    }
}
```

### Step 3: Access Your Documentation

Visit your application:
- **JSON Spec**: `http://localhost:8000/api/docs.json`
- **UI Documentation**: `http://localhost:8000/api/docs`

## 📚 Advanced Examples

### Example 1: Working with Doctrine Entities

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * List all products
     *
     * @return JsonResponse<array<Product>>
     */
    #[Route('', name: 'api_products_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        return $this->json($products);
    }

    /**
     * Get product by ID
     *
     * Scramble will automatically detect the Product entity
     * and document its properties.
     *
     * @param Product $product
     *
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'api_products_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($product);
    }

    /**
     * Create a new product
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route('', name: 'api_products_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice($data['price']);
        $product->setDescription($data['description'] ?? null);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, 201);
    }
}
```

### Example 2: Custom Configuration

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\RouteProvider;
use Dedoc\Scramble\Support\OperationBuilder;
use Symfony\Component\Routing\RouterInterface;

class ApiDocumentationService
{
    public function __construct(
        private RouterInterface $router
    ) {
    }

    public function generateDocumentation(string $version = 'v1'): array
    {
        $infer = new Infer();
        $operationBuilder = new OperationBuilder();
        $generator = new Generator($operationBuilder, $infer);

        $config = $this->createConfig($version);

        return $generator($config);
    }

    private function createConfig(string $version): GeneratorConfig
    {
        $config = new GeneratorConfig();

        // API Information
        $config->set('ui.title', 'My Company API');
        $config->set('info.version', $version);
        $config->set('info.description', 'Comprehensive API for managing resources');

        // Server Configuration
        $config->set('servers', [
            'Development' => 'http://localhost:8000',
            'Staging' => 'https://staging-api.example.com',
            'Production' => 'https://api.example.com',
        ]);

        // Route Filtering
        $config->routes(function ($route, $routeName) use ($version) {
            $path = $route->getPath();

            // Only include API routes for this version
            if (!str_starts_with($path, "/api/{$version}")) {
                return false;
            }

            // Exclude admin routes
            if (str_contains($path, '/admin')) {
                return false;
            }

            // Exclude internal routes
            if (str_starts_with($routeName, '_')) {
                return false;
            }

            return true;
        });

        return $config;
    }
}
```

### Example 3: Multiple API Versions

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ApiDocumentationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiVersionedDocsController extends AbstractController
{
    public function __construct(
        private ApiDocumentationService $docService
    ) {
    }

    #[Route('/api/v1/docs.json', name: 'api_v1_docs', methods: ['GET'])]
    public function v1Docs(): JsonResponse
    {
        return $this->json($this->docService->generateDocumentation('v1'));
    }

    #[Route('/api/v2/docs.json', name: 'api_v2_docs', methods: ['GET'])]
    public function v2Docs(): JsonResponse
    {
        return $this->json($this->docService->generateDocumentation('v2'));
    }
}
```

### Example 4: Type Inference with DTOs

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\CreateUserRequest;
use App\DTO\UserResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserDTOController extends AbstractController
{
    /**
     * Create a new user with DTO
     *
     * Scramble will infer the request structure from the DTO class.
     *
     * @param Request $request
     *
     * @return JsonResponse<UserResponse>
     */
    #[Route('', name: 'api_users_create_dto', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // In a real application, you would deserialize and validate
        $dto = new CreateUserRequest(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'secret'
        );

        $response = new UserResponse(
            id: 1,
            name: $dto->name,
            email: $dto->email,
            createdAt: new \DateTime()
        );

        return $this->json($response, 201);
    }
}

// DTO Classes
namespace App\DTO;

class CreateUserRequest
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {
    }
}

class UserResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public \DateTime $createdAt
    ) {
    }
}
```

## ⚙️ Configuration

### Basic Configuration Options

```php
$config = new GeneratorConfig();

// API Metadata
$config->set('ui.title', 'My API');
$config->set('info.version', '1.0.0');
$config->set('info.description', 'API documentation');

// Server URLs
$config->set('servers', [
    'Local' => 'http://localhost:8000',
    'Production' => 'https://api.example.com',
]);

// API Path Prefix
$config->set('api_path', 'api');

// API Domain (optional)
$config->set('api_domain', 'api.example.com');
```

### Route Filtering

```php
// Include only specific routes
$config->routes(function ($route, $routeName) {
    return str_starts_with($route->getPath(), '/api');
});

// Complex filtering
$config->routes(function ($route, $routeName) {
    $path = $route->getPath();

    // Include API routes
    if (!str_starts_with($path, '/api')) {
        return false;
    }

    // Exclude admin routes
    if (str_contains($path, '/admin')) {
        return false;
    }

    // Exclude specific HTTP methods
    $methods = $route->getMethods();
    if (in_array('OPTIONS', $methods)) {
        return false;
    }

    return true;
});
```

## 🧪 Testing

### Running Tests

```bash
# Install dependencies
composer install

# Run PHPUnit tests
composer test

# Run with coverage
composer test-coverage

# Run specific test
vendor/bin/phpunit tests/RouteProviderTest.php
```

### Code Quality

```bash
# Check code style (Symfony standards)
composer format-check

# Fix code style automatically
composer format

# Run static analysis
composer analyse
```

## 🛠️ Development

### Project Structure

```
src/
├── Generator.php              # Main documentation generator
├── GeneratorConfig.php        # Configuration management
├── RouteProvider.php          # Route collection from Symfony
├── RouteInfo.php             # Route information wrapper
├── Infer/                    # Type inference system
├── Reflection/               # Reflection utilities
│   └── RouteReflection.php
└── Support/                  # Helper classes and extensions

tests/
├── RouteProviderTest.php     # Route provider tests
└── RouteInfoTest.php         # Route info tests
```

### Extending Scramble

You can extend Scramble's functionality by:

1. **Custom Type Inference**: Add custom type inference logic
2. **Custom Extensions**: Create operation extensions
3. **Custom Transformers**: Add document transformers
4. **Custom Filters**: Implement custom route filters

## 📖 Documentation

For more detailed information, see:
- [SYMFONY_INTEGRATION.md](SYMFONY_INTEGRATION.md) - Complete integration guide
- [examples/SymfonyIntegration.php](examples/SymfonyIntegration.php) - Full working example

## 🤝 Contributing

Contributions are welcome! Please ensure:

1. All tests pass: `composer test`
2. Code follows Symfony standards: `composer format`
3. Static analysis passes: `composer analyse`
4. Add tests for new features

## 📋 Requirements

- PHP 8.1 or higher
- Symfony 6.0 or 7.0
- Composer 2.x

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details

## 🙏 Credits

- Original author: Roman Lytvynenko
- Built for Symfony applications
- Inspired by API documentation best practices

## 💡 Tips & Best Practices

### 1. Use Meaningful PHPDoc Comments

```php
/**
 * Get user by ID
 *
 * This endpoint retrieves detailed information about a specific user.
 * The user must exist in the database, otherwise a 404 error is returned.
 *
 * @param int $id The unique user identifier
 *
 * @return JsonResponse Returns user details in JSON format
 */
#[Route('/{id}', methods: ['GET'])]
public function show(int $id): JsonResponse
{
    // ...
}
```

### 2. Type Your Return Values

```php
// Good: Helps Scramble understand the response structure
public function list(): JsonResponse
{
    return $this->json($users);
}

// Better: With PHPDoc for complex types
/**
 * @return JsonResponse<array<User>>
 */
public function list(): JsonResponse
{
    return $this->json($users);
}
```

### 3. Use Route Requirements

```php
// Helps document parameter constraints
#[Route('/{id}', requirements: ['id' => '\d+'])]
public function show(int $id): JsonResponse
{
    // ...
}
```

### 4. Group Related Routes

```php
#[Route('/api/users')]
class UserController extends AbstractController
{
    // All routes automatically prefixed with /api/users
}
```

## 🔗 Useful Links

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Stoplight Elements](https://stoplight.io/open-source/elements) - API Documentation UI

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/dedoc/scramble/issues)
- **Discussions**: [GitHub Discussions](https://github.com/dedoc/scramble/discussions)

---

Made with ❤️ for the Symfony community
