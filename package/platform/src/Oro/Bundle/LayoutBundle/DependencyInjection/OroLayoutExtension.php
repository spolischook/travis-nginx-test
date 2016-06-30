<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Config\Loader\FolderingCumulativeFileLoader;

class OroLayoutExtension extends Extension
{
    const UPDATE_LOADER_SERVICE_ID = 'oro_layout.loader';
    const THEME_MANAGER_SERVICE_ID = 'oro_layout.theme_manager';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_layout',
            [
                new FolderingCumulativeFileLoader(
                    '{folder}',
                    '[a-zA-Z][a-zA-Z0-9_\-:]*',
                    new YamlCumulativeFileLoader('Resources/views/layouts/{folder}/theme.yml')
                ),
                new YamlCumulativeFileLoader('Resources/config/oro/layout.yml')
            ]
        );
        $themesResources    = $configLoader->load($container);
        $existThemePaths = [];
        foreach ($themesResources as $resource) {
            $existThemePaths[$resource->path] = true;
            $configs[] = $this->getThemeConfig($resource);
        }

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('block_types.yml');
        $loader->load('config_expressions.yml');
        $loader->load('collectors.yml');

        if ($config['view']['annotations']) {
            $loader->load('view_annotations.yml');
            $this->addClassesToCompile(['Oro\\Bundle\\LayoutBundle\\EventListener\\LayoutListener']);
        }

        $container->setParameter(
            'oro_layout.templating.default',
            $config['templating']['default']
        );
        if ($config['templating']['php']['enabled']) {
            $loader->load('php_renderer.yml');
            $container->setParameter(
                'oro_layout.php.resources',
                $config['templating']['php']['resources']
            );
        }
        if ($config['templating']['twig']['enabled']) {
            $loader->load('twig_renderer.yml');
            $container->setParameter(
                'oro_layout.twig.resources',
                $config['templating']['twig']['resources']
            );
        }

        $loader->load('theme_services.yml');
        if (isset($config['active_theme'])) {
            $container->setParameter('oro_layout.default_active_theme', $config['active_theme']);
        }
        $container->setParameter('oro_layout.debug', $config['debug']);
        $themeManagerDef = $container->getDefinition(self::THEME_MANAGER_SERVICE_ID);
        $themeManagerDef->replaceArgument(1, $config['themes']);

        $foundThemeLayoutUpdates = [];
        $updateFileExtensions    = [];
        $updateLoaderDef         = $container->getDefinition(self::UPDATE_LOADER_SERVICE_ID);
        foreach ($updateLoaderDef->getMethodCalls() as $methodCall) {
            if ($methodCall[0] === 'addDriver') {
                $updateFileExtensions[] = $methodCall[1][0];
            }
        }
        $updatesLoader = new CumulativeConfigLoader(
            'oro_layout_updates_list',
            [new FolderContentCumulativeLoader('Resources/views/layouts/', -1, false, $updateFileExtensions)]
        );

        $resources = $updatesLoader->load($container);
        foreach ($resources as $resource) {
            /**
             * $resource->data contains data in following format
             * [
             *    'directory-where-updates-found' => [
             *       'found update absolute filename',
             *       ...
             *    ]
             * ]
             */
            $resourceThemeLayoutUpdates = $this->filterThemeLayoutUpdates($existThemePaths, $resource->data);
            $resourceThemeLayoutUpdates = $this->sortThemeLayoutUpdates($resourceThemeLayoutUpdates);
            $foundThemeLayoutUpdates = array_merge_recursive($foundThemeLayoutUpdates, $resourceThemeLayoutUpdates);
        }

        $container->setParameter('oro_layout.theme_updates_resources', $foundThemeLayoutUpdates);

        $this->addClassesToCompile(['Oro\Bundle\LayoutBundle\EventListener\ThemeListener']);
    }

    /**
     * @param array $updates
     * @return array
     */
    protected function sortThemeLayoutUpdates(array $updates)
    {
        $directories = [];
        $files = [];
        foreach ($updates as $key => $update) {
            if (is_array($update)) {
                $update = $this->sortThemeLayoutUpdates($update);
                $directories[$key] = $update;
            } else {
                $files[] = $update;
            }
        }

        sort($files);
        ksort($directories);
        $updates = array_merge($files, $directories);

        return $updates;
    }

    /**
     * @param array $existThemePaths
     * @param array $themes
     * @return array
     */
    protected function filterThemeLayoutUpdates(array $existThemePaths, array $themes)
    {
        foreach ($themes as $theme => $themePaths) {
            foreach ($themePaths as $pathIndex => $path) {
                if (is_string($path) && isset($existThemePaths[$path])) {
                    unset($themePaths[$pathIndex]);
                }
            }
            if (empty($themePaths)) {
                unset($themes[$theme]);
            } else {
                $themes[$theme] = $themePaths;
            }
        }

        return $themes;
    }

    /**
     * @param CumulativeResourceInfo $resource
     * @return array
     */
    protected function getThemeConfig(CumulativeResourceInfo $resource)
    {
        if ($resource->name === 'layout') {
            return $resource->data['oro_layout'];
        } else {
            $themeName = basename(dirname($resource->path));
            return [
                'themes' => [
                    $themeName => $resource->data
                ]
            ];
        }
    }
}
