<?php

namespace Dedoc\Scramble\Http\Controllers;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class DocsController
{
    public function __construct(
        private Generator $generator,
        private GeneratorConfig $config
    ) {
    }

    public function ui(): Response
    {
        // In the future, we would render a proper view here.
        // For now, a simple HTML response is sufficient.
        $content = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Scramble API Documentation</title>
</head>
<body>
    <h1>Scramble API Documentation</h1>
    <p>JSON available at <a href="/docs/api.json">/docs/api.json</a></p>
</body>
</html>
HTML;
        return new Response($content);
    }

    public function json(): JsonResponse
    {
        $specification = ($this->generator)($this->config);

        return new JsonResponse($specification);
    }
}
