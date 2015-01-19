<?php

namespace Vivait\LicensingClientBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class VivaitLicensingClientExtension extends ConfigurableExtension
{
    /**
     * {@inheritdoc}
     */
    public function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $container->setParameter('vivait_licensing_client.client_id', $mergedConfig['client_id']);
        $container->setParameter('vivait_licensing_client.client_secret', $mergedConfig['client_secret']);
        $container->setParameter('vivait_licensing_client.application', $mergedConfig['app_name']);
        $container->setParameter('vivait_licensing_client.base_url', $mergedConfig['base_url']);
        $container->setParameter('vivait_licensing_client.debug', $mergedConfig['debug']);

        $loader->load('services.yml');
    }
}
