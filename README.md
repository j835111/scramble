<p align="center">
  <a href="https://scramble.dedoc.co" target="_blank">
    <img src="./.github/gh-img.png?v=1" alt="Scramble – Symfony API documentation generator"/>
  </a>
</p>

# Scramble for Symfony

Scramble generates API documentation for **Symfony** projects automatically, without requiring you to manually write PHPDoc annotations. The documentation is generated in the OpenAPI 3.1.0 format.

*Note: This is a port of the original Scramble package for Laravel.*

## Introduction

The main motto of the project is to generate your API documentation without requiring you to annotate your code.

This allows you to focus on your code and avoid annotating every possible parameter or field, which can lead to outdated documentation. By generating docs automatically from the code, your API will always have up-to-date documentation that you can trust.

## Installation

You can install the package via Composer:

```shell
composer require dedoc/scramble
```

Next, enable the bundle by adding it to the list of registered bundles in `config/bundles.php`:

```php
// config/bundles.php
return [
    // ...
    Dedoc\Scramble\ScrambleBundle::class => ['all' => true],
];
```

## Usage

After installation, the bundle will expose two routes in your application:

- `/docs/api` - A UI viewer for your documentation.
- `/docs/api.json` - The OpenAPI document in JSON format that describes your API.

You can customize the bundle's behavior by creating a configuration file at `config/packages/scramble.yaml`. Here is an example:

```yaml
# config/packages/scramble.yaml
scramble:
    # The path where the OpenAPI specification is served.
    api_path: /docs/api.json

    # The path where the API documentation UI is served.
    docs_path: /docs/api

    # Other configuration options...
```
