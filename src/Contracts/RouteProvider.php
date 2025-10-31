<?php

namespace Dedoc\Scramble\Contracts;

/**
 * Route Provider Contract
 *
 * Defines how to retrieve routes from different frameworks.
 */
interface RouteProvider
{
    /**
     * Get all available routes.
     *
     * @return array<int, RouteContract>
     */
    public function getRoutes(): array;

    /**
     * Get the framework identifier.
     */
    public function getFramework(): string;
}
