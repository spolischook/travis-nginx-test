<?php

namespace OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'oro_organization.form.type.organizations_select';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('OroPro\Bundle\OrganizationBundle\Form\Type\OrganizationsProSelectType');
        }
    }
}
