<?php

namespace Dedoc\Scramble\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension as BaseExtension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class Extension extends BaseExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Make the processed config available as a parameter
        $container->setParameter('scramble.config', $config);
    }

    public function getAlias(): string
    {
        return 'scramble';
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Load the bundle's routes
        $routesConfigFile = __DIR__.'/../Resources/config/routes.yaml';
        $config = Yaml::parseFile($routesConfigFile);
        $container->prependExtensionConfig('routing', $config);
    }
}
