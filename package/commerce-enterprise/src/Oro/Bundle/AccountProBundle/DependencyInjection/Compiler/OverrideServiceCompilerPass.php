<?php

namespace Oro\Bundle\AccountProBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\AccountProBundle\Datagrid\RolePermissionDatasource;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceIds = [
            'orob2b_account.datagrid.datasource.account_role_permission_datasource',
            'orob2b_account.datagrid.datasource.account_role_frontend_permission_datasource'
        ];

        foreach ($serviceIds as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $definition = $container->getDefinition($serviceId);
                $definition->setClass(RolePermissionDatasource::class);
                $definition->addMethodCall('addExcludePermission', ['SHARE']);
            }
        }
    }
}
