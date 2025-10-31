<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\DependencyInjection\Extension;
use Dedoc\Scramble\ScrambleBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new ScrambleBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'test' => true,
        ]);
        $c->loadFromExtension('scramble', [
            // your bundle config
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/../src/Resources/config/routes.yaml');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new Extension());
    }
}
