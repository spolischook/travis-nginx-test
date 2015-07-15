<?php

namespace Oro\Bundle\LDAPBundle;

use Oro\Bundle\LDAPBundle\DependencyInjection\Compiler\DynamicFieldsExtensionCompilerPass;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LDAPBundle\Security\Factory\FormLoginLdapFactory;

class OroLDAPBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        /** @var SecurityExtension $security */
        $security = $container->getExtension('security');
        $security->addSecurityListenerFactory(new FormLoginLdapFactory());

        $container->addCompilerPass(new DynamicFieldsExtensionCompilerPass());
    }
}
