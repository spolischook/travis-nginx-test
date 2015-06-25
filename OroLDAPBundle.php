<?php

namespace Oro\Bundle\LDAPBundle;

use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LDAPBundle\Security\Factory\FormLoginLdapFactory;

class OroLDAPBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if (!function_exists('ldap_connect')) {
            throw new \Exception("Module php-ldap isn't installed");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var SecurityExtension $security */
        $security = $container->getExtension('security');
        $security->addSecurityListenerFactory(new FormLoginLdapFactory());
    }
}
