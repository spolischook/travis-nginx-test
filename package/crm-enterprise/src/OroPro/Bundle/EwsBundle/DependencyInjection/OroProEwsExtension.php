<?php

namespace OroPro\Bundle\EwsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroProEwsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        // Load EWS configuration parameters to a DI container
        // The config parameter name is prefixed with 'oro_pro_ews.' before it is added to DI container
        // For example: 'wsdl_endpoint' config parameter is added to DI container as 'oro_pro_ews.wsdl_endpoint'
        $bundles          = $container->getParameter('kernel.bundles');
        $parametersFile   = $fileLocator->locate('parameters.yml');
        $ewsConfigContent = Yaml::parse(file_get_contents($parametersFile));
        $container->addResource(new FileResource($parametersFile));
        foreach ($ewsConfigContent['parameters'] as $key => $val) {
            $prmVal = array_key_exists($key, $config) ? $config[$key] : $val;
            if (0 === strpos($prmVal, '@')) {
                $bundleNameEndPos = strpos($prmVal, '/');
                $bundleName       = substr($prmVal, 1, $bundleNameEndPos - 1);
                if (isset($bundles[$bundleName])) {
                    $bundle = $bundles[$bundleName];
                    $relDir = substr($prmVal, $bundleNameEndPos);
                    if (DIRECTORY_SEPARATOR != '/') {
                        $relDir = str_replace('/', DIRECTORY_SEPARATOR, $relDir);
                    }
                    $prmVal = dirname((new \ReflectionClass($bundle))->getFileName()) . $relDir;
                }
            }
            $container->setParameter('oro_pro_ews.' . $key, $prmVal);
        }

        // Load services
        $loader = new Loader\YamlFileLoader($container, $fileLocator);
        $loader->load('services.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }
}
