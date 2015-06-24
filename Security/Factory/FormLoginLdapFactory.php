<?php

namespace Oro\Bundle\LDAPBundle\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationFormLoginFactory;

class FormLoginLdapFactory extends OrganizationFormLoginFactory
{

    public function getKey()
    {
        return 'oro-ldap-organization-form-login';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'oro_ldap.authentication.provider.' . $id;
        $container
            ->setDefinition(
                $provider,
                new DefinitionDecorator('oro_ldap.authentication.provider')
            )
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(2, $id);

        return $provider;
    }
}
