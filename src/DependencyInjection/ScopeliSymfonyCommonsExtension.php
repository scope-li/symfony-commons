<?php

namespace Scopeli\SymfonyCommons\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ScopeliSymfonyCommonsExtension extends Extension
{    
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('Scopeli\SymfonyCommons\Command\TranslationCommand');
        $definition->replaceArgument('$locale', $config['translation']['default_locale']);
    }
}
