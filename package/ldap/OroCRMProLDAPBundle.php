<?php

namespace OroCRMPro\Bundle\LDAPBundle;

use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroCRMPro\Bundle\LDAPBundle\DependencyInjection\Compiler\DynamicFieldsExtensionCompilerPass;
use OroCRMPro\Bundle\LDAPBundle\Security\Factory\FormLoginLdapFactory;

class OroCRMProLDAPBundle extends Bundle
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
