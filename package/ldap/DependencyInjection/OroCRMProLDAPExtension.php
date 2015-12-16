<?php

namespace OroCRMPro\Bundle\LDAPBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroCRMProLDAPExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $serviceLoader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $serviceLoader->load('services.yml');
        $serviceLoader->load('form.yml');
        $serviceLoader->load('importexport.yml');
    }
}
