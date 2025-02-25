<?php

namespace App\SnowflakeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Snowflake Bundle Dependency Injection Extension
 *
 * This extension loads the configuration for the Snowflake bundle and
 * registers services with the dependency injection container.
 */
class SnowflakeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set parameters from configuration
        $container->setParameter('app.snowflake.node_id', $config['node_id']);
        $container->setParameter('app.snowflake.epoch', $config['epoch']);

        // Load service definitions
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }
}