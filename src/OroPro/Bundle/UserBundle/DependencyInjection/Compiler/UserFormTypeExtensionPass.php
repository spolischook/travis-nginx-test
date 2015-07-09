<?php

namespace OroPro\Bundle\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserFormTypeExtensionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('oro_user.form.type.user')) {
            $definition = $container->getDefinition('oro_user.form.type.user');
            $definition->setClass('OroPro\Bundle\UserBundle\Form\Type\UserType');
        }
    }
}
