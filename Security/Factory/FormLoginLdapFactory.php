<?php

namespace OroCRMPro\Bundle\LDAPBundle\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory\OrganizationFormLoginFactory;

class FormLoginLdapFactory extends OrganizationFormLoginFactory
{

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'orocrmpro-ldap-organization-form-login';
    }

    /**
     * {@inheritdoc}
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'orocrmpro_ldap.authentication.provider.' . $id;
        $container
            ->setDefinition(
                $provider,
                new DefinitionDecorator('orocrmpro_ldap.authentication.provider')
            )
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(2, $id);

        return $provider;
    }
}
