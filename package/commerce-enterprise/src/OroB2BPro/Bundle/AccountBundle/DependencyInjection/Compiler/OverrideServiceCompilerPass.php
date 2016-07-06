<?php

namespace OroB2BPro\Bundle\AccountBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'orob2b_account.datagrid.datasource.account_role_frontend_permission_datasource';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroB2BPro\Bundle\AccountBundle\Datagrid\RolePermissionDatasource');
        }

        $serviceId = 'orob2b_account.datagrid.datasource.account_role_permission_datasource';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroB2BPro\Bundle\AccountBundle\Datagrid\RolePermissionDatasource');
        }
    }
}
